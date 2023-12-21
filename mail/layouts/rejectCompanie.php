<?php

/** @var yii\web\View $this */
/** @var mixed $content */
/** @var mixed $constants */


/** @var MessageInterface $message the message being composed */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap4\Breadcrumbs;
use yii\bootstrap4\Html;
use yii\bootstrap4\Nav;
use yii\bootstrap4\NavBar;
use yii\mail\MessageInterface;

?>
<?php $this->beginPage() ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=<?= Yii::$app->charset ?>" />
  <title><?= Html::encode($this->title) ?></title>
  <?php $this->head() ?>
</head>

<body>
  <?php $this->beginBody() ?>

  <div>
    <span style="opacity: 0"><?= date("Y-m-d H:i:s") ?> </span>
    <table width="100%" cellspacing="0" cellpadding="0">
      <tbody>
        <tr>
          <td valign="top">
            <table cellspacing="0" cellpadding="0" align="center" style="background-color: #f5f5f5; padding: 10px">
              <tbody>
                <tr>
                  <td align="center">
                    <table width="600" cellspacing="0" cellpadding="0" align="center">
                      <tbody>
                        <tr>
                          <td align="center">
                            <a href="<?= $constants['appWebDomain'] ?>" target="_blank" style="margin-top: 20px">
                              <img alt="logo" src="<?= $constants['logoUrl'] ?>" style="height: 40px; width: auto;margin-top: 20px" />
                            </a>
                            <h1 style="
                                          color: #E30613;
                                          /*font-family: lora, georgia, 'times new roman',serif;*/
                                          /*font-size: 50px;*/
                                          line-height: 1;
                                          margin-top: 30px;
                                          display: block;">
                              Solicitud de afiliación rechazada
                            </h1>
                          </td>
                        </tr>

                        <tr>
                          <td align="center" style="margin-top: 20px; padding-right: 10px; padding-left: 10px;">
                            <p style="color: #2e445c;font-weight: bold">
                              Estimado <?= $content["nombres"] ?> el registro de su
                              empresa <?=
                                      $content["companie"] ?> fue evaluada y
                              lamentablemente fue rechazado, debido a lo siguiente:
                            </p>
                            <p style="color: #2e445c; font-style: italic;margin: 10px 40px;">
                              <?= $content["rejection_reason"] ?>
                            </p>
                            <p style="color:#ffb300; font-weight: bold">
                              Nota: su cuenta ha sido excluida del sistema, debe ponerse
                              en contacto con los
                              administradores para cualquier reclamo.
                            </p>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
            <table cellpadding="0" cellspacing="0" align="center" style="background-color: #2e445c;padding: 10px">
              <tbody>
                <tr>
                  <td align="center">
                    <table width="600" cellspacing="0" cellpadding="0" align="center">
                      <tbody>
                        <tr>
                          <td align="center">

                            <h5 style="color: #fff; line-height: 0">
                              Contactanos:
                              <a style="color: #fff; margin-right: 5px;"> Teléfono-Fax: <?= $constants['phone'] ?></a>
                              |
                              <a style="color: #fff;margin-left: 5px;" target="_blank" href="mailto:<?= $constants['appEmail'] ?>"><?= $constants['appEmail'] ?></a>
                            </h5>
                            <h5 style="color: #fff; line-height: 0">
                              Este correo se envió a
                              <a style="color: #fff;" target="_blank" href="mailto:<?= $content["email"] ?>"><?= $content["email"] ?></a>
                              desde beneficios porque se suscribio.
                            </h5>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
          </td>
        </tr>
      </tbody>
    </table>
    <span style="opacity: 0"><?= date("Y-m-d H:i:s") ?> </span>

  </div>

  <?php $this->endBody() ?>

</body>

</html>
<?php $this->endPage() ?>