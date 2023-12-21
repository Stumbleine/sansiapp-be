<?php


namespace app\models;

use app\controllers\AuthController;
use app\controllers\UtilController;
use Yii;
use yii\base\Model;

/**
 * ContactForm is the model behind the contact form.
 */
class ForgotPasswordMail extends Model
{
  public $name = "Sistema de beneficios estudiantiles UMSS";
  public $email = 'noreply20@umss.edu.bo';
  public $subject = "Restablecer contraseña";
  public $body = "Correo de cambio de contraseña";

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
      // verifyCode  needs to be entered correctly
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


  public function ForgotPassword($user, $pwd = null)
  {
    $contants = UtilController::getEmailConstants();
    $data['pwd'] = $pwd;
    if ($this->validate()) {
      $message = Yii::$app->mailer->compose("@app/mail/layouts/resetPassword", ["user" => $user, 'data' => $data, 'constants' => $contants]);
      $message->setTo($user['email'])
        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
        ->setSubject($this->subject)
        ->setTextBody($this->body);
      if ($message->send()) {
        return true;
      } else {
        return false;
      }
    }
    return false;
  }
}
