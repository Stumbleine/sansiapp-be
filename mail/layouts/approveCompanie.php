<?php

/** @var yii\web\View $this */
/** @var mixed $content */
/** @var mixed $constants */

/** @var MessageInterface $message the message being composed */

use yii\bootstrap4\Html;
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
                                          color: #5CB85C;

                                          line-height: 1;
                                          margin-top: 30px;
                                          display: block;">
                              Solicitud de afiliación aceptada
                            </h1>
                          </td>
                        </tr>

                        <tr>
                          <td align="center" style="padding-right: 10px; padding-left: 10px; margin-top:20px">
                            <p style="color: #2e445c; font-weight: bold">
                              Estimado <?= $content["nombres"] ?> el registro de su
                              empresa <?= $content["companie"] ?> fue evaluada y
                              aprobada su afiliación, ahora sus ofertas y productos
                              serán visibles
                              a la población estudiantil.
                            </p>
                            <p style="color: #2e445c; font-style: italic;margin: 10px 40px;">
                              "¡Crea ofertas, beneficia estudiantes y promociona
                              marcas!"
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
                              <a style="color: #fff; margin-right: 5px;"> Teléfono-Fax:
                                <?= $constants['phone'] ?> </a>
                              |
                              <a style="color: #fff;margin-left: 5px;" target="_blank" href="mailto:<?= $constants['appEmail'] ?>"><?= $constants['appEmail'] ?></a>
                            </h5>
                            <h5 style="color: #fff; line-height: 0">
                              Este correo se envió a
                              <a style="color: #fff;" target="_blank" href="mailto:<?= $content["email"] ?>"><?= $content["email"] ?></a>
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