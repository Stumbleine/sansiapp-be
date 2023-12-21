<?php


namespace app\models;

use app\controllers\AuthController;
use app\controllers\UtilController;
use Yii;
use yii\base\Model;

/**
 * ContactForm is the model behind the contact form.
 */
class WelcomeMail extends Model
{
  public $name = "Sistema de beneficios estudiantiles umss";
  public $email = 'noreply20@umss.edu.bo';
  public $subject = "Bienvenido";
  public $body = "Correo de bienvenida";

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


  public function Welcome($user, $auth = false, $pwd = null)
  {
    $constants = UtilController::getEmailConstants();
    $extraData['pwd'] = $pwd;
    $extraData['text1'] = 'ahora ya puedes registrar tu empresa para crear ofertas y añadir productos directamente desde la aplicación web.';
    $extraData['text2'] = '¡Crea tus ofertas, beneficia estudiantes y promociona tu marca!';
    if ($this->validate()) {
      if (!$auth) {
        $message = Yii::$app->mailer->compose(
          "@app/mail/layouts/welcome",
          ["user" => $user, 'data' => $extraData, 'constants' => $constants]
        );
      } else {
        $message = Yii::$app->mailer->compose(
          "@app/mail/layouts/welcomeWithAuth",
          ["user" => $user, 'data' => $extraData, 'constants' => $constants]
        );
      }
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

  public function WelcomeAdmin($user, $pwd)
  {
    $constants = UtilController::getEmailConstants();
    $extraData['rol'] = 'administrador';
    $extraData['pwd'] = $pwd;
    $extraData['text1'] = 'ahora ya puedes gestionar usuarios, empresas, ofertas y productos directamente desde la aplicación web.';
    $extraData['text2'] = '!Crea ofertas, beneficia estudiantes y promociona marcas!';
    $email = $user['email'];
    if ($this->validate()) {
      $message = Yii::$app->mailer->compose(
        "@app/mail/layouts/welcomeWithAuth",
        ["user" => $user, 'data' => $extraData, 'constants' => $constants]
      );
      $message->setTo($email)
        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
        ->setReplyTo([Yii::$app->params['adminEmail'] => "Sistema de beneficios estudiantiles umss"])
        ->setSubject($this->subject)
        ->setTextBody($this->body)
        ->send();
      return true;
    }
    return false;
  }

  public function WelcomeCashier($user, $pwd)
  {
    $constants = UtilController::getEmailConstants();
    $extraData['pwd'] = $pwd;
    $extraData['text1'] = 'como cajero, ahora podrás verificar códigos de canje para tu empresa desde la aplicación web.';
    $extraData['text2'] = '!Beneficia estudiantes!';
    if ($this->validate()) {
      $message = Yii::$app->mailer->compose(
        "@app/mail/layouts/welcomeWithAuth",
        ["user" => $user, 'data' => $extraData, 'constants' => $constants]
      );
      $message->setTo($user->email)
        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
        ->setReplyTo([Yii::$app->params['adminEmail'] => "Sistema de beneficios estudiantiles umss"])
        ->setSubject($this->subject)
        ->setTextBody($this->body)
        ->send();
      return true;
    }
    return false;
  }
}
