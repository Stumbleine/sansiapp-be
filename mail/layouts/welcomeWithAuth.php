<?php

/** @var yii\web\View $this */
/** @var mixed $user */
/** @var mixed $data */
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
                            <h1 style="
                                          color: #333333;
                                          font-size: 50px;
                                          line-height: 1;
                                          display: block;
                                          margin-top: 20px
                                        ">
                              <em style="color: #003770">Bienvenido a </em>
                            </h1>
                            <a href="<?= $constants["appWebDomain"] ?>" target="_blank">
                              <img alt="logo" src=<?= $constants["logoUrl"] ?> style="height: 40px; width: auto" />
                            </a>
                          </td>
                        </tr>
                        <tr>
                          <td align="center">
                            <h3 style="
                                          color: #003770;
                                          line-height: 0;
                                          padding-top: 30px;
                                        ">
                              Hola
                              <?= $user["nombres"] ?>
                            </h3>
                          </td>
                        </tr>
                        <tr>
                          <td align="center" style=" padding-right: 10px; padding-left: 10px;">
                            <p style="color: #2e445c">
                              Nos alegra verte aquí, tu cuenta fue registrado
                              exitosamente dentro del Sistema de Beneficios
                              Estudiantiles UMSS
                              <a href="<?= $constants["appWebDomain"] ?>" target="_blank">
                                <u style="color: #2e445c; font-weight:bold">beneficios</u></a>
                              <?= $data["text1"] ?>
                            </p>
                            <p style="color: #2e445c; font-style: italic;margin: 10px 40px;">
                              <?= $data["text2"] ?>

                            </p>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
            <table cellspacing="0" cellpadding="0" align="center" style="
                  background-color: #f5f5f5;
                  padding-left: 10px;
                  padding-right: 10px;
                ">
              <tbody>
                <tr>
                  <td align="center">
                    <table width="600" cellspacing="0" cellpadding="0" align="center">
                      <tbody>
                        <tr>
                          <td align="left">
                            <table width="600" cellspacing="0" cellpadding="0">
                              <tbody>
                                <tr>
                                  <td align="center" style="margin-top: 10px">
                                    <h3 style="color: #2e445c">
                                      Contraseña:
                                      <u style=" margin-left: 5px;
                                                            color: #673AB7;
                                                            text-decoration: none;
                                                            font-size: 20px;
                                                          ">
                                        <?= $data['pwd'] ?>
                                      </u>
                                    </h3>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </td>
                        </tr>

                        <tr>
                          <td align="left">
                            <table width="100%" cellspacing="0" cellpadding="0" style="padding: 10px">
                              <tbody width="600" valign="top">
                                <tr>
                                  <td align="center">
                                    <span style="
                                                  padding: 15px;
                                                  border: 1px solid #2e445c;
                                                  border-radius: 7px;
                                                  cursor: pointer;
                                                  margin: 10px 10px;
                                                ">
                                      <a href="<?= $constants["appWebDomain"] ?>" target="_blank" style="
                                                    text-decoration: none;
                                                    color: #2e445c;
                                                ">Ir al sitio web</a>
                                    </span>
                                    <p style="
                                                      padding-top: 20px;
                                                      font-size: 13px;
                                                      color:#ffb300;
                                                    ">
                                      Nota: la contraseña generada es provisional, se
                                      recomienda actualizar a una nueva.
                                    </p>
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
                              <a style="color: #fff; margin-right: 5px;"> Teléfono-Fax:
                                <?= $constants['phone'] ?> </a>
                              |
                              <a style="color: #fff;margin-left: 5px;" target="_blank" href="mailto:" <?= $constants["appEmail"] ?> ""><?= $constants["appEmail"] ?></a>
                            </h5>
                            <h5 style="color: #fff; line-height: 0">
                              Este correo se envió a
                              <a style="color: #fff;" target="_blank" href="mailto:<?= $user["email"] ?>"><?= $user["email"] ?></a>
                              desde beneficios porque se suscribió.
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