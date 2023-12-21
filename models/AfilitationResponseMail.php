<?php


namespace app\models;

use app\controllers\AuthController;
use app\controllers\UtilController;
use Yii;
use yii\base\Model;

/**
 * ContactForm is the model behind the contact form.
 */
class AfilitationResponseMail extends Model
{
  public $name = "Sistema de beneficios estudiantiles UMSS";
  public $email = 'noreply20@umss.edu.bo';
  public $subject = "Respuesta a afilicacion";
  public $body = "Correo de respuesta a solicitud";

  //    public $verifyCode;


  /**
   * @return array the validation rules.
   */
  public function rules()
  {
    return [
      // name, email, subject and body are required
      [['name', 'email', 'subject', 'body'], 'required'],
      // email has to be a valid email address
      ['email', 'email'],
      // verifyCode needs to be entered correctly
      //            ['verifyCode', 'captcha'],
    ];
  }

  /**
   * @return array customized attribute labels
   */
  public function attributeLabels()
  {
    return [
      'verifyCode' => 'Verification Code',
    ];
  }


  public function Response($user, $companie, $reject = false)
  {
    $contants = UtilController::getEmailConstants();

    $content = [
      "nombres" => $user['nombres'],
      "email" => $user['email'],
      "companie" => $companie['razon_social'],
      "rejection_reason" => $reject ? $companie['rejection_reason'] : ''
    ];
    $email = $user['email'];
    if ($this->validate()) {
      if ($reject) {
        $message = Yii::$app->mailer->compose("@app/mail/layouts/rejectCompanie", ["content" => $content, 'constants' => $contants]);
      } else {
        $message = Yii::$app->mailer->compose("@app/mail/layouts/approveCompanie", ["content" => $content, 'constants' => $contants]);
      }
      $message->setTo($email)
        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
        ->setSubject($this->subject)
        ->setTextBody($this->body)
        ->send();
      return true;
    }
    return false;
  }
}
