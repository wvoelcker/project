<?php # vim:ts=2:sw=2:et:
/* Copyright (c) 2007, OmniTI Computer Consulting, Inc.
 * All Rights Reserved.
 * For licensing information, see:
 * http://labs.omniti.com/alexandria/trunk/LICENSE
 */


/**
 * 
 * NB adapted for use here by addition of support for embedded Images
 * 
 */
 

/**
 * Abstracts the mail transport interface used by the mailer class.
 */
interface IOmniTI_Mail_Transport {
  /**
   * Returns a string representing the smtp status code,
   * or true if the message will be sent asynchronously.
   *
   * @parameter envelopes an array of envelopes, described below
   * @parameter rfc2822Message the message, complete with headers
   *
   * Each envelope in the envelopes array is itself an array with
   * three elements; the zeroth element is the return path address,
   * the first element is the recipient address and the second element
   * is an optional submission ID to uniquely identify the individual
   * mailing for reporting purposes.
   */
  function submitMessage($envelopes, $rfc2822Message);

  /**
   * Returns an array keyed by the optional uniqueID you used
   * to submit the mail.
   * the values of the array are the envelope from and to addresses,
   * and the smtp status code for that message.
   * May return true to indicate that results are pending, or false
   * to indicate that nothing is pending.
   * Set $blocking to true to wait for results, false to poll and return
   * immediately.
   */
  function getAsyncStatus($blocking);

  /**
   * Returns true if the transport requires canonical line endings to be used.
   * Canonical line endings are CRLF.
   */
  function useCanonicalLineEndings();

  /**
   * Returns true if the transport requires the message to be "dot-stuffed"
   * as per RFC 2821.
   */
  function useSMTPDotStuffing();
}

/**
 * A high performance mail injection transport.
 *
 * This transport requires the Alexandria smtp_injector extension to be
 * loaded.
 */
class OmniTI_Mail_Transport_SMTP_Injector implements IOmniTI_Mail_Transport {
  private $inj = null;

  /** the SMTP server into which to inject mail */
  public $smtp_server = '127.0.0.1';
  /** the port number to use when talking to $smtp_server */
  public $smtp_port = '25';
  /** maximum backlog.
   * If this number is exceeded, the injector will wait for mail submissions
   * to complete before proceeding.  This option is to avoid consuming too
   * much memory. */
  public $max_backlog = 1024;
  /** maximum number of connections to establish to $smtp_server.
   * The actual number established varies depending on the backlog of mail.
   * The more mail queued, the more connections that will be established. */
  public $max_outbound_connections = 32;
  /** log file for diagnostic information */
  public $log_file = null;
  /** log level
   * Controls verbosity of the log file.  0 logs errors only, with higher
   * numbers logging more information */
  public $log_level = 0;
  /** the domain to pass during EHLO
   * If set to null, will default to the value returned by your system
   * gethostname(3) library function. */
  public $ehlo_domain = null;
  
  function __construct() {
    if (!extension_loaded('smtp_injector')) {
      throw new Exception("you need to load the OmniTI SMTP Injector extension");
    }
  }

  function submitMessage($envelopes, $rfc2822Message)
  {
    if (!$this->inj) {
      /* ghetto parameter passing */
      ini_set('smtp_injector.default_server', $this->smtp_server);
      ini_set('smtp_injector.default_port', $this->smtp_port);
      ini_set('smtp_injector.backlog', $this->max_backlog);
      ini_set('smtp_injector.max_outbound_connections', $this->max_outbound_connections);
      if ($this->ehlo_domain) {
        ini_set('smtp_injector.ehlo_domain', $this->ehlo_domain);
      } else {
        ini_restore('smtp_injector.ehlo_domain');
      }
      if ($this->log_level && $this->log_file) {
        ini_set('smtp_injector.debug_log', $this->log_file);
        ini_set('smtp_injector.debug_level', $this->log_level);
      } else {
        ini_restore('smtp_injector.debug_log');
        ini_restore('smtp_injector.debug_level');
      }
      $this->inj = smtp_injector_create();
      if (!$this->inj) throw new Exception("failed to create injector thread");
    }
    $res = array();
    foreach ($envelopes as $env) {
      if (!smtp_injector_mail($this->inj, $env[0], $env[1], $rfc2822Message, $env[2])) {
        $res[] = array($env[0], $env[1], "550 injection failed", $env[2]);
      }
    }
    return $res;
  }

  function getAsyncStatus($blocking)
  {
    $status = smtp_injector_get_status($this->inj, $blocking);
    $res = array();
    if (is_array($status['results'])) foreach ($status['results'] as $stat) {
      if (isset($stat['key'])) {
        $res[$stat['key']] = array($stat['from'], $stat['to'], $stat['res']);
      } else {
        $res[] = array($stat['from'], $stat['to'], $stat['res']);
      }
    }
    if (count($res)) return $res;
    if (!$status['pending']) return false;
    return true;
  }

  function useCanonicalLineEndings()
  {
    return true;
  }

  function useSMTPDotStuffing()
  {
    return true;
  }
}

/**
 * A transport that sends using the builtin mail() function.
 *
 * This is provided more as an example than a suggestion for production.
 * This implementation drops the display name from the To: header.
 *
 * We recommend using OmniTI_Mail_Transport_SMTP_Injector instead.
 */
class OmniTI_Mail_Transport_Builtin implements IOmniTI_Mail_Transport {
  private $onWindows = false;
  function __construct() {
    if (!strncasecmp(PHP_OS, 'win', 3)) {
      $this->onWindows = true;
    }
  }

  function submitMessage($envelopes, $rfc2822Message)
  {
    $res = array();

    $crlf = $this->onWindows ? "\r\n" : "\n";
    list($headers, $body) = explode("$crlf$crlf", $rfc2822Message, 2);
    $hdrs = array();
    $subject = "";
    foreach (explode($crlf, $headers) as $hdr) {
      if (!strncasecmp("subject:", $hdr, 8)) {
        $subject = trim(substr($hdr, 9));
        continue;
      }
      if (!strncasecmp("to:", $hdr, 3)) {
        /* strip out the to header, because mail() will
         * add one back in.
         * This is mildly retarded, because we'll drop the
         * display name portion along with it.
         */
        continue;
      }
      $hdrs[] = $hdr;
    }
    $headers = implode($crlf, $hdrs);
    foreach ($envelopes as $env) {
      $x = mail($env[1], $subject, $body, $headers, "-f" . $env[0]) ? "250 ok" : "550 failed";
      $res[] = array($env[0], $env[1], $x, $env[2]);
    }
    return $res;
  }

  function getAsyncStatus($blocking)
  {
    return false;
  }

  function useCanonicalLineEndings()
  {
    return $this->onWindows;
  }

  function useSMTPDotStuffing()
  {
    return $this->onWindows;
  }
}

/**
 * A class for constructing and submitting mail.
 *
 * This class takes care of constructing mail and submitting over a mail transport.
 */
class OmniTI_Mail_Mailer {
  /** @var IOmniTI_Mail_Transport */
  static protected $transport;

  /** the envelope from address.
   * @var string
   */
  protected $returnPath;
  
  /** the envelope to addresses. */
  protected $rcptList = array();

  /** pattern to use for auto verping */
  protected $verpPattern = null;

  /** charset for textual body */
  protected $textBodyCharset = "us-ascii";
  /** textual body */
  protected $textBody = null;
  /** charset for html body */
  protected $htmlBodyCharset = "us-ascii";
  /** html body */
  protected $htmlBody = null;

  /** additional headers list */
  protected $customHeaders = array();
 
  // Will V change: embedded images
  protected $embeddedimages = array();

  /**
   * Set the preferred mail transport.
   * If not set, the default OmniTI_Mail_Transport_Builtin transport
   * will be used */
  static function setTransport(IOmniTI_Mail_Transport $transport) {
    self::$transport = $transport;
  }

  /**
   * Set the Return-Path for the mail.
   * You should call this method after calling setFrom() if you
   * want the return path to be different from the From address.
   * See also setVerpPattern().
   */
  function setReturnPath($address) {
    $this->returnPath = $address;
  }

  /** enables auto-verp generation.
   *
   * Pattern is a string that contains placeholders:
   *  %TO% expands to a verp safe representation of the recipient
   *  %ID% expands to the uniqueID for the recipient
   *
   * \code
   * $mailer->setVerpPattern("bounces-%ID%@my.domain");
   * $mailer->setVerpPattern("bounces-%TO%@my.domain");
   * \endcode
   */
  function setVerpPattern($pattern) {
    $this->verpPattern = $pattern;
  }

  /** removes all recipients
   */
  function clearRecipientList() {
    $this->rcptList = null;
  }

  /** add a recipient.
   *
   * @parameter address the rfc2821 address for the recipient
   * @parameter display optional display name for the recipient
   * @parameter header which header the recipient should be listed in (default To:)
   * @parameter uniqueID optional reporting ID for tracking this recipient
   * @parameter charset optional charset which specifies the encoding of the display parameter
   *
   * If you omit $charset, and $display contains 8-bit characters, an
   * exception will be raised.
   */
  function addRecipient($address, $display = null, $header = 'To', $uniqueID = null, $charset = null) {
    if ($charset === null && preg_match('/[\x7f-\xff]/', $display)) {
      throw new Exception("non-ascii passed as the display name, you need to also supply the charset parameter");
    }

    $this->rcptList[] = array($address, $display, $header, $uniqueID, $charset);
  }

  /** set the plain text content for the message.
   *
   * @parameter text the content to set for the message
   * @parameter charset the encoding of the text parameter (default us-ascii)
   *
   * The $charset MUST match the encoding used for $text, otherwise the email
   * you send will be invalid and likely rejected.
   *
   * If you also call setBodyHTML(), a multipart/alternative email will be
   * generated with both plain text and HTML parts.
   */
  function setBodyText($text, $charset = "us-ascii") {
    $this->textBody = $text;
    $this->textBodyCharset = $charset;
  }

  /** set the HTML content for the message.
   *
   * @parameter html the content to set for the message
   * @parameter charset the encoding of the text parameter (default us-ascii)
   *
   * The $charset MUST match the encoding used for $html, otherwise the email
   * you send will be invalid and likely rejected.
   *
   * If you also call setBodyText(), a multipart/alternative email will be
   * generated with both plain text and HTML parts.
   */
  function setBodyHTML($html, $charset = "us-ascii") {
    $this->htmlBody = $html;
    $this->htmlBodyCharset = $charset;
  }
 
   // Will V change: embed an image
   function embedImage($filename, $mimetype, $data) {
		$contentid = sizeOf($this->embeddedimages).".".getmypid().".".time()."@willv.net";
		$this->embeddedimages[] = array("filename"=>$filename, "mimetype"=>$mimetype, "data"=>$data, "contentid"=>$contentid);
		return $contentid;
   }

  /** set an arbitrary header for the message, replacing an existing value.
   *
   * @parameter name the name of the header
   * @parameter value the value of the header
   * @parameter charset optional charset specifying the encoding for the header value
   *
   * If charset is specified, the header will be encoded as per RFC2047.
   */
  function setHeader($name, $value, $charset = null) {
    if ($charset === null && preg_match('/[\x7f-\xff]/', $value)) {
      throw new Exception("non-ascii passed as the value, you need to also supply the charset parameter");
    }

    $this->removeHeader($name);
    $this->addHeader($name, $value, $charset);
  }

  /** add an arbitrary header for the message.
   *
   * You may call addHeader multiple times with the same $name;
   * each call will generate an additional $name header in the message.
   *
   * @parameter name the name of the header
   * @parameter value the value of the header
   * @parameter charset optional charset specifying the encoding for the header value
   *
   * If charset is specified, the header will be encoded as per RFC2047.
   */
  function addHeader($name, $value, $charset = null) {
    $this->customHeaders[strtolower($name)][] = array($name, $value, $charset);
  }

  /** remove all instances of a header from the the message.
   *
   * @parameter name the name of the header to remove.
   */
  function removeHeader($name) {
    unset($this->customHeaders[strtolower($name)]);
  }

  /** set the subject for the message.
   *
   * @parameter subject the subject text
   * @parameter charset optional charset specifying the encoding of the subject
   *
   * If charset is specified, the header will be encoded as per RFC2047.
   */
  function setSubject($subject, $charset = null) {
    if ($charset === null && preg_match('/[\x7f-\xff]/', $subject)) {
      throw new Exception("non-ascii passed as the subject, you need to also supply the charset parameter");
    }
    $this->setHeader('Subject', $subject, $charset);
  }

  /** set the From address for the message.
   *
   * @parameter address the rfc2821 address
   * @parameter display display name of the sender
   * @parameter charset optional charset specifying the encoding of the display name
   *
   * This function will set the return path to $address, if it is not already set.
   * It will also set the From: header for the message.
   *
   * If charset is specified, the display name will be encoded as per RFC2047.
   */
  function setFrom($address, $display, $charset = null) {
    if (!$this->returnPath) {
      $this->returnPath = $address;
    }
    if ($charset) {
      $display = $this->rfc2047EncodeHeader($display, $charset);
    } else {
      if ($charset === null && preg_match('/[\x7f-\xff]/', $display)) {
        throw new Exception("non-ascii passed as the display name, you need to also supply the charset parameter");
      }
      $display = "\"$display\"";
    }
    $this->setHeader('From', "$display <$address>", null);
  }

  /** send the message.
   *
   * If multiple recipients were specified, mail will be sent to each.
   *
   * Returns an array of status information, one per recipient.
   * Each status element consists of an array containing the return path,
   * the recipient, the unique id and the SMTP transaction status.
   *
   * Your code should be able to handle an empty array, which indicates
   * that the submission status is not yet known.  You can poll
   * the transport using its IOmniTI_Mail_Transport::getAsyncStatus() method
   * to check up on asynchronous sends.
   */
  function send() {
    if (self::$transport === null) {
      self::$transport = new OmniTI_Mail_Transport_Builtin;
    }

    $status = array();

    /* break the recipient list into unique batches */
    $batches = array();

    foreach ($this->rcptList as $recip) {
      if ($this->verpPattern) {
        $batches[] = array($recip);
      } else {
        if ($recip[3] === null) {
          $batches['@'][] = $recip;
        } else {
          $batches[$recip[3]][] = $recip;
        }
      }
    }

    foreach ($batches as $recips) {
      $message = $this->generateMail($recips);
     
//      echo $message;

      $status = array();

      /* build up the envelopes list */
      if ($this->verpPattern) {
        foreach ($recips as $recip) {
          $to = $recip[0];
          $id = $recip[3];
          $verp_to = str_replace(array('=', '@'), array('==', '='), $to);
          $from = str_replace(array('%ID%', '%TO%'), array($id, $verp_to), $this->verpPattern);
         
          $st = self::$transport->submitMessage(array(array($from, $to, $id)), $message);
          $status = array_merge($status, $st);
        }
      } else {
        $from = $this->returnPath;
        $env = array();
        foreach ($recips as $recip) {
          $to = $recip[0];
          $id = $recip[3];
          $env[] = array($from, $to, $id);
        }
        $st = self::$transport->submitMessage($env, $message);
        $status = array_merge($status, $st);
      }
    }
    return $status;
  }

  /** generates a boundary string to use for MIME part separation */
  function generateBoundary() {
    /* =_ prevents collisions when using quoted-printable encoding */
    return "Alexandria=_" . md5(uniqid(""));
  }

  /** encodes a header per RFC2047 */
  function rfc2047EncodeHeader($value, $charset) {
    return "=?$charset?b?" . base64_encode($value) . "?=";
  }

  /** Generates and RFC2822 message from the state of the mailer object.
   *
   * This function is called internally by the send() method; you won't usually
   * need to call it yourself.
   */
  function generateMail($rcptList)
  {
    $crlf = self::$transport->useCanonicalLineEndings() ? "\r\n" : "\n";
    
    $msg = "";
    
    $headers = $this->customHeaders;

    $recips_by_header = array();
    
    foreach ($rcptList as $recip) {
      if ($recip[2] !== null) {
        $charset = $recip[4];
        $display = $recip[1];
        $address = $recip[0];
        if ($charset) {
          $display = $this->rfc2047EncodeHeader($display, $charset);
        } else {
          $display = "\"$display\"";
        }
        $key = ucfirst(strtolower($recip[2]));
        
        $recips_by_header[$key][] = "$display <$address>";
      }
    }
    $headers['mime-version'] = array(array('Mime-Version', '1.0'));

    $body = null;

    /* figure out overall content type */
    if (strlen($this->htmlBody) && strlen($this->textBody)) {
      /* multipart/alternative */
      $boundary = $this->generateBoundary();
      $headers['content-type'] = array(array('Content-Type',
        "multipart/alternative;$crlf\tboundary=\"$boundary\"", null));
      $body = 'both';
    } else if (strlen($this->htmlBody)) {
      /* text/html */
      $headers['content-type'] = array(array('Content-Type',
        'text/html; charset="' . $this->htmlBodyCharset . '"'));
      $body = 'html';

      $te = null;
      $html = $this->transferEncodePiece($this->htmlBody, $this->htmlBodyCharset, $te);
      $headers['content-transfer-encoding'] = array(array('Content-Transfer-Encoding', $te));
    } else {
      /* text/plain */
      $headers['content-type'] = array(array('Content-Type',
        'text/plain; charset="' . $this->textBodyCharset . '"'));
      $body = 'text';
      $te = null;
      $text = $this->transferEncodePiece($this->textBody, $this->textBodyCharset, $te);
      $headers['content-transfer-encoding'] = array(array('Content-Transfer-Encoding', $te));
    }

    foreach ($headers as $hdrs) foreach ($hdrs as $hdr) {
      if (isset($hdr[2]) and $hdr[2] !== null) {
        $value = $this->rfc2047EncodeHeader($hdr[1], $hdr[2]);
      } else {
        $value = $hdr[1];
      }
      $msg .= $hdr[0] . ': ' . $value . $crlf;
    }
    foreach ($recips_by_header as $hdrname => $recips) {
      $msg .= $hdrname . ': ' . implode(",$crlf\t", $recips) . $crlf;
    }
    $msg .= $crlf;
    
    if ($body == 'both') {
      $msg .= "--$boundary$crlf";
      $msg .= "Content-Type: text/plain; charset=\"$this->textBodyCharset\"$crlf";

      $te = null;
      $text = $this->transferEncodePiece($this->textBody, $this->textBodyCharset, $te);
      $msg .= "Content-Transfer-Encoding: $te$crlf$crlf$text$crlf";

      $msg .= "$crlf--$boundary$crlf";

	  // Will V change:embeddedimages
	  if (sizeOf($this->embeddedimages)) {
		  $innerboundary = $this->generateBoundary();
		  $msg .= "Content-Type: multipart/related; boundary=\"".$innerboundary."\"$crlf$crlf--".$innerboundary."$crlf";
	  }
	  // End change
      $msg .= "Content-Type: text/html; charset=\"$this->htmlBodyCharset\"$crlf";

      $te = null;
      $html = $this->transferEncodePiece($this->htmlBody, $this->htmlBodyCharset, $te);
      $msg .= "Content-Transfer-Encoding: $te$crlf$crlf$html$crlf";

	  // Will V change:embeddedimages
	  if (sizeOf($this->embeddedimages)) {
			foreach($this->embeddedimages as $embeddedimage) {
				$msg .= "$crlf--$innerboundary$crlf";
				$msg .= "Content-Type: ".$embeddedimage["mimetype"]."; name=\"".$embeddedimage["filename"]."\"$crlf";
				$msg .= "Content-ID: <".$embeddedimage["contentid"].">$crlf";
				$msg .= "Content-Transfer-Encoding: base64$crlf$crlf";
				$msg .= chunk_split(base64_encode($embeddedimage["data"]), 76, "\n")."$crlf$crlf";
			}
			$msg .= "--$innerboundary--";
	  }
	  // End Will V change

      $msg .= "$crlf--$boundary--$crlf";
    } else if ($body == 'html') {
		// Will V TODO:WV:20090505:Embedded images with HTML-only messages not yet supported
      $msg .= $html;
    } else if ($body == 'text') {
      $msg .= $text;
    }
    
    if (self::$transport->useSMTPDotStuffing()) {
      $msg .= "$crlf.$crlf";
    }

    return $msg;
  }

  /** applies appropriate transfer encoding to a piece of a message.
   *
   * You won't typically need to call this yourself.
   */
  function transferEncodePiece($content, $charset, &$encoding) {
    $encoding = '7bit';
    $crlf = self::$transport->useCanonicalLineEndings() ? "\r\n" : "\n";
    
    /* anything not 7-bit in here? */

#    $pref = 'quoted-printable';
    $pref = 'base64';
    
    if (preg_match('/[\x7f-\xff]/', $content)) {
      if (!strcasecmp($charset, 'us-ascii')) {
        throw new Exception("you specified us-ascii for the charset, but non ascii was present");
      }
      if ($pref == 'quoted-printable') {
        $encoding = 'quoted-printable';
        $stuff = self::$transport->useSMTPDotStuffing();

        $content = str_replace('=', '=3D', $content);
        $encoded = "";
        foreach (explode("\n", $content) as $line) {
          $line = rtrim($line, "\r\n");
          if (!strncasecmp($line, 'From ', 5)) {
            $line = '=46rom ' . substr($line, 5);
          }
          if ($line == '.') {
            $line = '=2E';
          } else if ($line[0] == '.' && $stuff) {
            $line = '.' . $line;
          }

          $last = substr($line, -1);
          if ($last == ' ' || $last == "\t") {
            $line .= '=';
          }

          $q = "";
          $l = strlen($line);
          for ($i = 0; $i < $l; $i++) {
            $c = ord($line[$i]);
            if (($c >= 37 && $c <= 60) || $c == 62 || $c == 63 || 
                ($c >= 65 && $c <= 90) || $c == 95 ||
                ($c >= 97 && $c <= 122) || ($c == 32) || ($c == 9)) {
              /* natural, EBCDIC safe set */
              $c = $line[$i];
            } else {
              /* must quote */
              $c = '=' . strtoupper(dechex($c));
            }
            /* wrap long lines */
            if (strlen($q) + strlen($c) > 75) {
              /* look backwards for a space */
              $broke = false;
              for ($j = strlen($q) - 1; $j > 1; $j--) {
                if ($q[$j] == ' ' || $q[$j] == "\t") {
                  $encoded .= substr($q, 0, $j) . '=' . $crlf;
                  $q = substr($q, $j) . $c;
                  $broke = true;
                  break;
                }
              }
              if (!$broke) {
                $encoded .= $q . '=' . $crlf;
                $q = $c;
              }
              continue;
            }
            $q .= $c;
          }
          $encoded .= $q . $crlf;
        }
        return $encoded;
      }
      $encoding = 'base64';
      return chunk_split(base64_encode($content), 76, $crlf);
    } else if (self::$transport->useSMTPDotStuffing()) {
      return preg_replace('/^\./m', '..', $content);
    } else {
      return $content;
    }
  }
}

