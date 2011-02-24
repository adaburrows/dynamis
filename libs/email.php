<?php
class Email {

  // class variables
  var $sender;
  var $reply_to;
  var $errors_to;
  var $recipients;
  var $bcc;
  var $subject;
  var $headers;
  var $content;
  var $content_parts;
  var $attachments;

  /*
   * PHP4 style constructor
   */
  function Email() {
    // Call the true constructor
    $this->__construct();
  }

  /*
   * PHP5 style constructor
   */
  function __construct() {
    $this->errors_to = "support@Ulynk.com";
    $attachments =false;
    $content_parts =false;
  }

  /*
   * Sets the sender of the email message
   * Email address must comply with RFC 2822
   */
  function setSender($sender) {
    $this->sender = $sender;
  }

  function setReplyTo($reply_to) {
    $this->reply_to = $reply_to;
  }

  /*
   * Sets the list of recipients
   * Accepts a comma-separated list or array of email addresses
   * Email addresses must comply with RFC 2822
   */
  function setRecipients($recipientList) {
    if(is_array($recipientList)) {
      $recipientList = implode(", ", $recipientList);
    }
    $this->recipients = $recipientList;
  }

  /*
   * Sets the list of BCC recipients
   * Accepts a comma-separated list or array of email addresses
   * Email addresses must comply with RFC 2822
   */
  function setBcc($recipientList) {
    if(is_array($recipientList)) {
      $recipientList = implode(", ", $recipientList);
    }
    $this->bcc = $recipientList;
  }

  /*
   * Sets the subject line of the message
   * Subject must satisfy RFC 2047.
   */
  function setSubject($subject) {
    $this->subject = $subject;
  }

  /*
   * Adds a message part with a given mime type
   */
  function addMessagePart($content_part, $content_type = "text") {
    switch($content_type) {
      case "text":
        $content_type = "text/plain; charset=UTF-8;";
        break;
      case "html":
        $content_type = "text/html; charset=UTF-8;";
        break;
      default:
        break;
    }
    $this->content_parts[] = array(
      'content' => $content_part,
      'content-type' => $content_type
    );    
  }

  /*
   * Adds an attachment given a file name
   */
  function addAttachment($path, $content_type = null) {
    if ($content_type == null) {
      //$content_type = mime_content_type($path);
    }

    $attachment = array(
      'filename' => $path,
      'name' => basename($path),
      'content_type' => $content_type
    );

    $this->attachments[] = $attachment;
  }

  /*
   * converts text to html formatted to work for email within a given email template
   */
  function text2html($text, $template) {
    $url_pattern = "@\b(https?://)?(([0-9a-zA-Z_!~*'().&=+$%-]+:)?[0-9a-zA-Z_!~*'().&=+$%-]+\@)?(([0-9]{1,3}\.){3}[0-9]{1,3}|([0-9a-zA-Z_!~*'()-]+\.)*([0-9a-zA-Z][0-9a-zA-Z-]{0,61})?[0-9a-zA-Z]\.[a-zA-Z]{2,6})(:[0-9]{1,4})?((/[0-9a-zA-Z_!~*'().;?:\@&=+$,%#-]+)*/?)@";
    $message = nl2br($text);
    $message = preg_replace($url_pattern, '<a href=3D"$0">$0</a>', $message);
    $data = array('message' => $message);
    $html = layout::view($template, $data, true);
    return ($html);
}

  /*
   * Sends out a mas email, without disclosing users' emails
   */
  function sendMassEmail($recipients, $subject, $text, $template) {

    $html = $this->text2html($text, $template);
    
    $this->setSender('support@ulynk.com');
    $this->setReplyTo('support@ulynk.com');
    $this->setRecipients('undisclosed-list@ulynk.com');
    $this->setBcc($recipients);
    $this->setSubject($subject);
    $this->addMessagePart($text);
    $this->addMessagePart($html, "html");
    $this->send();
  }

  /*
   * Builds and then sends the message
   */
  function send() {
    if (!$this->reply_to) {
      $this->reply_to = $this->sender;
    }
    $this->buildMessage();
    mail($this->recipients, $this->subject, $this->message, $this->headers);
  }

  /*
   * Does all the work of building a message
   */
  function buildMessage() {
    $boundary  = md5(uniqid(time()));
    $headers  = '';
    $message  = '';

    $headers .= "MIME-Version: 1.0\n";
    $headers .= "From: {$this->sender}\n";
    $headers .= "Reply-to: {$this->reply_to}\n";
    $headers .= "Bcc: {$this->bcc}\n";
    $headers .= "Return-path: {$this->reply_to}\n";
    $headers .= "Errors-to: {$this->errors_to}\n";
    $headers .= "X-Mailer: uLynk Platform\n";           
    $headers .= "Content-Type: multipart/alternative;\n\tboundary=$boundary\n";

    $this->headers = $headers;

    if($this->content_parts) {
      // add each part to the message
      foreach ($this->content_parts as $part) {
        $message .= "\n--$boundary\n";
        $message .= "Content-Type: {$part['content-type']}\n"; // sets the mime type
        $message .= "Content-Transfer-Encoding: Quoted-printable\n";
        $message .= "Content-Disposition: inline\n\n";
        $message .= wordwrap($part['content'], 70);
        $message .= "\n\n";
      }
    }
    if($this->attachments) {
      // add each attachment to the message
      foreach ($this->attachments as $file) {
        $filedata = file_get_contents($file['filename']);
        $content_type = $file['content_type']==null ? 'application/octet-stream' : $file['content_type'];

        $message .= "--$boundary"."\n";
        $message .= "Content-Type:" . $content_type . ";\n\tname=".$file['name']."\n";
        $message .= "Content-Transfer-Encoding: base64\n";
        $message .= "Content-Disposition: attachment;\n\tfilename=".$file['name']."\n\n";
        $message .= chunk_split(base64_encode($filedata));
        $message .= "\n\n";
      }
    }
    $message .= "--$boundary--";

    $this->message = $message;
  }

}
