<?php
class Mailer
{
    public function send($to, $body)
    {
        $message = (new Swift_Message('Intranet'))->setTo($to)->setBody($body);
        return (new Swift_Mailer(new Swift_SendmailTransport()))->send($message);
    }
}
