<?php

return [
  'adminEmail' => 'noreply20@umss.edu.bo',
  'senderEmail' => 'noreply20@umss.edu.bo',
  'senderName' => 'beneficios estudiantiles',
  'password' => 'upsi2018',
  'jwt' => [
    'issuer' => 'Beneficios estudiantiles umss',  //name of your project (for information only)
    'audience' => 'Estudiantes umss',  //description of the audience, eg. the website using the authentication (for info only)
    'id' => 'UMSSBENEFICIOS77[*]=',  //a unique identifier for the JWT, typically a random string
    'expire' => 30000,  //the short-lived JWT token is here set to expire after 5 min.
  ],
  'apiDomainDev' => 'https://localhost:8080/',
  'apiDomainProd' => 'https://beneficios.dube.umss.edu.bo/be/',
  'appWebDomain' => 'https://beneficios.dube.umss.edu.bo/index',
  'logoUrl' => 'https://beneficios.dube.umss.edu.bo/be/logo-sansi-app.png',
  'appEmail' => 'beumss@umss.edu',
  'phone' => '(591) 4 - 4233926',
  'sisLogUsr' => 'usuarioBeneficios01',
  'sisLogPwd' => 'ben123SIS',
  'sisLogUrl' => 'http://167.157.7.65:3001/',
  'pathLogs' => dirname(__DIR__) . '/log/logs',
];
