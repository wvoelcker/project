<?php # vim:ts=2:sw=2:et:
/* Copyright (c) 2006-2007, OmniTI Computer Consulting, Inc.
 * All Rights Reserved.
 * For licensing information, see:
 * http://labs.omniti.com/alexandria/trunk/LICENSE
 */

/**
 * A streaming MIME parser.
 *
 * Given a seekable stream, takes one pass to parse the structure of the
 * message.  Methods can then be called to extract the parts of interest.
 *
 * It requires the iconv extension for its charset and rfc2047 routines; iconv
 * is always present in PHP 5 builds, as it is required by libxml2.

\code
$fp = fopen("/path/to/mail", "rb");
// if you want to use a string instead:
//$fp = OmniTI_Util_StringStream::create($string);

// this call parses the mime message from the stream, creating
// a tree of the mime parts.
$message = new OmniTI_Mail_MimePart($fp);

// get a raw header
echo $message->headers['in-reply-to'];
// get a header, decoding rfc2047 and returning utf8
echo $message->getHeader('subject');

// Iterate all the parts
$parts = array();
$message->getThreaded($parts);
foreach ($parts as $part) {
  // show info about attachments
  if ($part->isAttachment()) {
    printf("attachment: content type=%s, filename=%s\n",
       $part->contenttype, $part->filename);
  }
}

// Iterate over the plain text parts:
// (this is a recursive call through the mime tree)
$plain_parts = array();
$message->getThreaded($plain_parts, 'text/plain');
foreach ($plain_parts as $part) {
  // echo the content
  echo $part->getBody($fp);
}
\endcode

The key concept is that parsing and extraction are separate phases; this
allows you to inspect the structure before committing resources to
decode and store the message parts.

 */
class OmniTI_Mail_MimePart {
  var $offset;
  var $length;

  var $headers = array();

  var $parts = null;
  var $bodyoffset;
  var $bodylen;
  var $boundary = null;
  var $endboundary = null;
  var $contenttype = "text/plain";

  /**
   * get the value of a header.
   * decodes RFC2047 encoding on the header, returning it in the specified
   * charset (default utf-8)
   */
  public function getHeader($name, $charset='utf-8') {
    if (isset($this->headers[$name])) {
      return iconv_mime_decode($this->headers[$name], 0, $charset);
    }
    return false;
  }

  protected function addHeader($header) {
    preg_match('/^([a-zA-Z0-9_-]+)\s*:\s*(.*)$/', $header, $matches);
    $hdr = strtolower($matches[1]);
    $value = $matches[2];

    if (isset($this->headers[$hdr])) {
      if (!is_array($this->headers[$hdr])) {
        $this->headers[$hdr] = array(
            $this->headers[$hdr],
            $value);
      } else {
        $this->headers[$hdr][] = $value;
      }
    } else {
      $this->headers[$hdr] = $value;
    }
  }

  /* parse a "Content-Type" style header which has name="value"
   * sub-values, separated by ;
   */
  protected function parseHeaderKeys($header) {
    list($val, $remainder) = explode(";", $header, 2);

    $retval = array();

    $retval['@'] = trim($val);

    $remainder = trim($remainder);

    while (strlen($remainder)) {
      if (!preg_match('/^([a-zA-Z0-9_-]+)=(.)/', $remainder, $matches)) {
        break;
      }

      $name = $matches[1];
      $delim = $matches[2];

      if ($delim != '\'' && $delim != '"') {
        $pattern = '/(\S+)(\s|$)/';
        $remainder = substr($remainder, strlen($name)+1);
      } else {
        $pattern = "/(((\\.)|[^$delim])+)$delim/";
        $remainder = substr($remainder, strlen($name)+2);
      }

      if (!preg_match($pattern, $remainder, $matches)) {
        break;
      }

      $retval[$name] = $matches[1];

      $remainder = substr($remainder, strlen($matches[0]));
    }
    return $retval;
  }

  protected function determineBoundary() {
    /* in theory, we're supposed to check that mime-version is 1.0 */

    if (!isset($this->headers['content-type']) ||
        count($this->headers['content-type']) != 1) {
      return 0;
    }
    $ct = $this->parseHeaderKeys($this->headers['content-type']);
    $this->contenttype = $ct['@'];
    if (isset($ct['boundary'])) {
      $this->boundary = "--" . $ct['boundary'];
      $this->endboundary = "--" . $ct['boundary'] . "--";
      return 1;
    }
    return 0;
  }

  function __construct($fp, $parent = null) {
    static $booted = false;

    if (!$booted) {
      if (!function_exists("iconv_mime_decode") || !function_exists("iconv")) {
        throw new Exception("iconv support is required");
      }
      $booted = true;
    }

    if ($parent !== null) {
      /* inherit parent boundaries, so that a non-multipart
       * part knows when to stop.
       * These are overridden when the mime headers for
       * this part are parsed */
      $this->headers['mime-version'] = '1.0';
      $this->boundary = $parent->boundary;
      $this->endboundary = $parent->endboundary;
    }

    $this->offset = ftell($fp);

    $in_header = true;
    $curr_header = "";

    /* true if *this* part is a multipart type */
    $is_container = false;

    while (true) {
      if (!$in_header) $last_offset = ftell($fp);
      $line = fgets($fp);
      if ($line === false || strlen($line) == 0) break;

      if (strlen($curr_header)) {
        /* continuation? */
        if ($line{0} === ' ' || $line{0} === "\t") {
          $curr_header .= rtrim($line, "\r\n");
          continue;
        }
        $this->addHeader($curr_header);
        $curr_header = "";
      }

      if ($in_header) {
        if ($line == "\r\n" || $line == "\n") {
          $in_header = false;
          $this->bodyoffset = ftell($fp);
          $is_container = $this->determineBoundary();
          continue;
        }
        $curr_header = rtrim($line, "\r\n");
        continue;
      }


      if ($this->boundary) {
        $line = rtrim($line, "\r\n");
        if ($line == $this->boundary) {

          if (!$is_container) {
            /* end of our section; seek back to the start of
             * the line so our parent/sibling is aware of the
             * boundary */
            fseek($fp, $last_offset);
            break;
          }
          $this->parts[] = new MimePart($fp, $this);

        } else if ($line == $this->endboundary) {
          /* end of our section; seek back to the start of
           * the line so our parent/sibling is aware of the
           * boundary */
          fseek($fp, $last_offset);
          break;
        }
      }
    }

    $this->length = $last_offset - $this->offset;
    $this->bodylen = $last_offset - $this->bodyoffset;
  }

  /**
   * returns the body of this mime part.
   * content-transfer-encoding is decoded.
   * if the content type indicates that the content has a particular
   * character encoding, that encoding is transformed to the specified
   * character set, default is utf-8.
   */
  function getBody($fp, $charset = 'utf-8') {
    fseek($fp, $this->bodyoffset);
    $data = stream_get_contents($fp, $this->bodylen);

    if (isset($this->headers['content-transfer-encoding'])) {
      $te = $this->headers['content-transfer-encoding'];
      if ($te == 'base64') {
        $data = base64_decode($data);
      } else if ($te == 'quoted-printable') {
        $data = quoted_printable_decode($data);
      } else if ($te == '7bit' || $te == '8bit' || $te == 'binary') {
        /* that's ok */
      } else {
        throw new Exception("unknown transfer-encoding $te");
      }
    }

    $ct = $this->parseHeaderKeys($this->headers['content-type']);
    if (isset($ct['charset']) && $charset != '8bit') {
      $converted = iconv($ct['charset'], $charset . '//IGNORE', $data);
      if ($converted !== false)
        $data = $converted;
    }

    return $data;
  }

  /**
   * streams the body of this mime part into another stream,
   * decoding transfer encoding and character encoding as it does so.
   * TODO: actually stream it....
   */
  function streamBodyTo($fp, $destfp, $charset = 'utf-8')
  {
    fseek($fp, $this->bodyoffset);
    $data = stream_get_contents($fp, $this->bodylen);

    if (isset($this->headers['content-transfer-encoding'])) {
      $te = $this->headers['content-transfer-encoding'];
      if ($te == 'base64') {
        $data = base64_decode($data);
      } else if ($te == 'quoted-printable') {
        $data = quoted_printable_decode($data);
      } else if ($te == '7bit' || $te == '8bit' || $te == 'binary') {
        /* that's ok */
      } else {
        throw new Exception("unknown transfer-encoding $te");
      }
    }

    $ct = $this->parseHeaderKeys($this->headers['content-type']);
    if (isset($ct['charset']) && $charset != '8bit') {
      $converted = iconv($ct['charset'], $charset . '//IGNORE', $data);
      if ($converted !== false)
        $data = $converted;
    }

    fwrite($destfp, $data);
  }

  function getThreaded(&$th, $preference = null) {
    if (is_array($this->parts)) {
      /* we are a container */
      if ($preference === null) {
        $th[] = $this;
      }
      foreach ($this->parts as $p) {
        $p->getThreaded($th, $preference);
      }
    } else if ($preference !== null) {
      if ($this->contenttype == $preference || $this->isAttachment()) {
        $th[] = $this;
      }
    } else {
      $th[] = $this;
    }
  }

  function isAttachment() {
    if (isset($this->filename)) return true;

    $ct = $this->parseHeaderKeys($this->headers['content-type']);
    $cd = $this->parseHeaderKeys($this->headers['content-disposition']);

    if (isset($ct['name']) || isset($cd['filename'])) {
      if (isset($ct['name']))
        $this->filename = $ct['name'];
      else
        $this->filename = $cd['filename'];
      return true;
    }
    return false;
  }
}

