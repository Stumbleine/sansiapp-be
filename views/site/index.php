<?php

/** @var yii\web\View $this */
use yii\helpers\Html;
$this->title = 'My Yii Application';
?>
<div class="site-index mb-5">

    <div class="jumbotron text-center bg-transparent">
        <div class="row">
            <div class="col-6">
                <h4 class="display-4">BIENVENID@ AL SISTEMA DE BENEFICIOS ESTUDIANTILES DE LA <b style="color: red;">UNIVERSIDAD</b> <b style="color: blue;">MAYOR DE SAN SIMON</b></h4>
            </div>
            <div class="col-6">
                <?= Html::img('@web/logo-sansi-app.svg', ['alt'=>'some', 'class'=>'thing', 'width'=>'600']);?>
            </div>
        </div>
    </div>

    <div class="body-content">

        <div class="row">
            <div class="col-lg-4">
                <h2>Acerca del carnet universitario</h2>

                <p>La Dirección Universitaria de Bienestar Estudiantil (D.U.B.E) fue creada mediante una resolución rectoral el 13 de julio de 1995 con el objetivo de planificar, ejecutar programas y proyectos orientados a mejorar la calidad de vida de los estudiantes de la Universidad Mayor de San Simón. Dentro de sus objetivos también se encuentran la carnetización de la población estudiantil mediante un proceso simple y ágil.
                Dicho carnet sirve como un documento de identificación y respaldado de ser estudiante de la Universidad Mayor de San Simón además de habilitar a los estudiantes para acceder a beneficios estudiantiles tales como:</p>
                <ul>
                    <li>Acceso a los diferentes laboratorios de la UMSS</li>
                    <li>Pagar pasaje universitario mostrando el respectivo carnet.</li>
                    <li>Préstamo de libros en las bibliotecas de las diferentes facultades de la universidad.</li>
                    <li>Beneficios en empresas y proveedores con las que la universidad tiene convenio.</li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h2>Acerca de los beneficios para estudiantes</h2>

                <p>
                
                El sistema contará con una aplicación móvil enfocada en facilitar a los estudiantes el acceso a la información y la obtención de beneficios estudiantiles que provee el carnet universitario.
Esta aplicación ademas servirá como un carnet universitario digital volviéndose así en una alternativa tecnología al carnet universitario actual sin perder los beneficios que el carnet ya provee.</p>

            </div>
            <div class="col-lg-4">
                <h2>Acerca de los beneficios para proveedores</h2>

                <p>El sistema contará con un sitio web administrativo dirigida principalmente a proveedores, en la cual estos podrán administrar
                    sus beneficios para los estudiantes. De la misma forma permitira a un administrador el control de proveedores y usuarios en general. Dichas
                acciones podrán ser reflejadas en la aplicación móvil previamente descrita.</p>

            </div>
        </div>

    </div>
</div>
