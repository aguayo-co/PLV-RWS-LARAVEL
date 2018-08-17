<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:300,500,600,700|Roboto+Condensed:400,700|Roboto:100,400'">

  <title>Bienvenida</title>

  <style>
    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 300;
      src: local('Montserrat Light'), local('Montserrat-Light'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_cJD3gnD_vx3rCs.woff) format('woff');
      unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
    }


    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 500;
      src: local('Montserrat Medium'), local('Montserrat-Medium'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_ZpC3gnD_vx3rCs.woff) format('woff');
      unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
    }


    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 600;
      src: local('Montserrat SemiBold'), local('Montserrat-SemiBold'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_bZF3gnD_vx3rCs.woff) format('woff');
      unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
    }


    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 700;
      src: local('Montserrat Bold'), local('Montserrat-Bold'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_dJE3gnD_vx3rCs.woff) format('woff');
      unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
    }

    body {
      margin: 0;
      background-color: #F6F6F6;
      font-family: Montserrat, Arial, Helvetica, sans-serif;
    }

    .main {
      display: block;
      width: 100%;
      max-width: 600px;
      margin: 0 auto;
      background-color: #ffffff;
    }

    .row {
      width: 100%;
    }

    .table {
      width: 100%;
      min-width: 100%;
      max-width: 600px;
      margin: 0 auto;
      border-collapse: collapse;
      -ms-text-size-adjust: 100%;
      -webkit-text-size-adjust: 100%;
    }

    .cell {
      padding-right: 0;
      padding-left: 0;
      padding-top: 0;
      padding-bottom: 0;
    }

    .cell_header {
      text-align: center;
      background-color: #000000;
    }

    .header {
      display: inline-block;
      padding: 20px 0 10px;
    }

    .img_brand {
      width: 200px;
    }

    .title {
      display: inline-block;
      margin: 50px 0 40px;
      font-size: 32px;
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
      line-height: 1.4;
      text-align: center;
      text-transform: uppercase;
      border-bottom: 1px solid #979797;
    }

    .highlight {
      color: #f65a66;
    }

    .center {
      text-align: center;
    }

    .kicker {
      display: block;
      width: 92%;
      margin: 0 auto 40px;
      line-height: 1.4;
      text-align: center;
      font-size: 18px;
      text-transform: uppercase;
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
      line-height: 1.4;
    }

    .txt {
      display: block;
      width: 90%;
      margin: 0 auto 30px;
      font-size: 18px;
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
      line-height: 1.4;
    }

    .txt-kiss {
      font-size: 18px;
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
      line-height: 1.4;
    }

    .btn {
      display: block;
      width: 70%;
      max-width: 410px;
      padding: 18px 6px;
      margin: 0 auto 20px;
      border: 2px solid #000000;
      text-decoration: none;
      font-size: 14px;
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
      line-height: 1.4;
      text-align: center;
      color: #000000;
    }

    .btn_solid {
      background-color: #000000;
      color: #ffffff;
    }

    .btn_last {
      margin-bottom: 40px;
    }

    .cell_highlight {
      margin-top: 30px;
      display: block;
      color: #ffffff;
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
      line-height: 1.4;
      background-color: #f65a66;
    }

    .byline {
      display: block;
      width: 92%;
      padding: 20px 0;
      margin: 0 auto;
      line-height: 1.4;
      text-align: center;
      font-size: 18px;
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
      line-height: 1.4;
      text-transform: uppercase;
    }

    .nav {
      display: block;
      margin-bottom: 30px;
    }

    .link {
      display: inline-block;
      margin: 5px;
      text-align: center;
      color: #000000;
      border-bottom: 2px solid #f65a66;
      text-decoration: none;
      font-size: 14px;
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
      line-height: 1.4;
    }

    .nav__link {
      padding-top: 30px;
    }

    .author {
      display: inline-block;
      margin: 24px 0 50px;
    }

    .cell_footer {
      background-color: #F6F6F6;
    }

    .footer__nav {
      display: inline-block;
      margin-bottom: 24px;
    }

    .footer__link {
      display: inline-block;
      padding: 0 16px;
    }

    .footer__copyright {
      display: block;
      max-width: 300px;
      margin: 0 auto 24px;
      text-align: center;
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
      line-height: 1.4;
    }

    .footer__txt {
      padding: 20px;
      margin: 0;
      font-size: 14px;
      font-family: 'Montserrat', arial;
      line-height: 1.4;
    }

  </style>
</head>

<body style="margin-top:0;margin-bottom:0;margin-right:0;margin-left:0;background-color:#F6F6F6;font-family:Montserrat, Arial, Helvetica, sans-serif;">
  <div class="main" style="display:block;width:100%;max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;background-color:#ffffff;">
    <table class="table" style="width:100%;min-width:100%;max-width:600px;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;border-collapse:collapse;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;">
      <!-- Header -->
      @include('email.template_header')
      <!-- end Header -->
      <tr class="row" style="width:100%;">
        <td class="cell center" style="padding-right:0;padding-left:0;padding-top:0;padding-bottom:0;text-align:center;">
          <h1 class="title" style="display:inline-block;margin-top:50px;margin-bottom:40px;margin-right:0;margin-left:0;font-size:32px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;text-align:center;text-transform:uppercase;border-bottom-width:1px;border-bottom-style:solid;border-bottom-color:#979797;">Hola
            <span class="highlight" style="color:#f65a66;">{{ $user->first_name }}</span>
          </h1>
        </td>
      </tr>
      <tr class="row" style="width:100%;">
        <td class="cell" style="padding-right:0;padding-left:0;padding-top:0;padding-bottom:0;">
          <p class="kicker" style="display:block;width:92%;margin-top:0;margin-bottom:40px;margin-right:auto;margin-left:auto;line-height:1.4;text-align:center;font-size:18px;text-transform:uppercase;font-family:'Montserrat', Arial, Helvetica, sans-serif;">Bienvenida a la comunidad de Prilovers.</p>
          <p class="kicker" style="display:block;width:92%;margin-top:0;margin-bottom:40px;margin-right:auto;margin-left:auto;line-height:1.4;text-align:center;font-size:18px;text-transform:uppercase;font-family:'Montserrat', Arial, Helvetica, sans-serif;">¡Ya somos más de
            <span class="highlight" style="color:#f65a66;">70.000</span> mujeres que compartimos nuestro clóset!</p>
        </td>
      </tr>
      <tr class="row" style="width:100%;">
        <td class="cell center" style="padding-right:0;padding-left:0;padding-top:0;padding-bottom:0;text-align:center;">
          <p class="txt" style="display:block;width:90%;margin-top:0;margin-bottom:30px;margin-right:auto;margin-left:auto;font-size:18px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">Para empezar el camino a la felicidad máxima, puedes:</p>
        </td>
      </tr>
      <tr class="row" style="width:100%;">
        <td class="cell" style="padding-right:0;padding-left:0;padding-top:0;padding-bottom:0;">
          <a href="{{ env('APP_FRONT_URL') }}publicar-venta" class="btn btn_solid" style="display:block;width:70%;max-width:410px;padding-top:18px;padding-bottom:18px;padding-right:6px;padding-left:6px;margin-top:0;margin-bottom:20px;margin-right:auto;margin-left:auto;border-width:2px;border-style:solid;border-color:#000000;text-decoration:none;font-size:14px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;text-align:center;background-color:#000000;color:#ffffff;">Subir la ropa que ya no usas</a>
        </td>
      </tr>
      <tr class="row" style="width:100%;">
        <td class="cell center" style="padding-right:0;padding-left:0;padding-top:0;padding-bottom:0;text-align:center;">
          <a href="{{ env('APP_FRONT_URL') }}" class="link nav__link" style="display:inline-block;margin-top:5px;margin-bottom:5px;margin-right:5px;margin-left:5px;text-align:center;color:#000000;border-bottom-width:2px;border-bottom-style:solid;border-bottom-color:#f65a66;text-decoration:none;font-size:14px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;padding-top:30px;">Visitar el shop y quizás comprar una nueva joyita</a>
        </td>
      </tr>
      <tr class="row" style="width:100%;">
        <td class="cell cell_highlight" style="padding-right:0;padding-left:0;padding-top:0;padding-bottom:0;margin-top:30px;display:block;color:#ffffff;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;background-color:#f65a66;">
          <p class="byline" style="display:block;width:92%;padding-top:20px;padding-bottom:20px;padding-right:0;padding-left:0;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;line-height:1.4;text-align:center;font-size:18px;font-family:'Montserrat', Arial, Helvetica, sans-serif;text-transform:uppercase;">Si quieres ser una Prilover experta, te recomendamos revisar nuestros tips:</p>
        </td>
      </tr>
      <tr class="row" style="width:100%;">
        <td class="cell center" style="padding-right:0;padding-left:0;padding-top:0;padding-bottom:0;text-align:center;">
          <nav class="nav" style="display:block;margin-bottom:30px;">
            <a href="{{ env('APP_FRONT_URL') }}como-funciona-prilov" class="link nav__link" style="display:inline-block;margin-top:5px;margin-bottom:5px;margin-right:5px;margin-left:5px;text-align:center;color:#000000;border-bottom-width:2px;border-bottom-style:solid;border-bottom-color:#f65a66;text-decoration:none;font-size:14px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;padding-top:30px;">Cómo funciona</a>
            <a href="{{ env('APP_FRONT_URL') }}aumentar-ventas" class="link nav__link" style="display:inline-block;margin-top:5px;margin-bottom:5px;margin-right:5px;margin-left:5px;text-align:center;color:#000000;border-bottom-width:2px;border-bottom-style:solid;border-bottom-color:#f65a66;text-decoration:none;font-size:14px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;padding-top:30px;">Cómo vender más</a>
            <a href="{{ env('APP_FRONT_URL') }}como-comprar" class="link nav__link" style="display:inline-block;margin-top:5px;margin-bottom:5px;margin-right:5px;margin-left:5px;text-align:center;color:#000000;border-bottom-width:2px;border-bottom-style:solid;border-bottom-color:#f65a66;text-decoration:none;font-size:14px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;padding-top:30px;">Cómo comprar</a>
            <a href="{{ env('APP_FRONT_URL') }}preguntas-frecuentes" class="link nav__link" style="display:inline-block;margin-top:5px;margin-bottom:5px;margin-right:5px;margin-left:5px;text-align:center;color:#000000;border-bottom-width:2px;border-bottom-style:solid;border-bottom-color:#f65a66;text-decoration:none;font-size:14px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;padding-top:30px;">Preguntas frecuentes</a>
          </nav>
        </td>
      </tr>
      <tr class="row" style="width:100%;">
        <td class="cell" align="center" style="padding-right:0;padding-left:0;padding-top:0;padding-bottom:0;">
          <p class="byline" style="display:block;width:92%;padding-top:20px;padding-bottom:20px;padding-right:0;padding-left:0;margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;line-height:1.4;text-align:center;font-size:18px;font-family:'Montserrat', Arial, Helvetica, sans-serif;text-transform:uppercase;">Cualquier duda, escríbenos. Siempre estaremos felices de ayudarte.
          </p>
          <p>
            <span class="highlight txt-kiss" style="color:#f65a66;font-size:18px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">Kisses!</span>
          </p>
        </td>
      </tr>
      <tr class="row" style="width:100%;">
        <td class="cell center" style="padding-right:0;padding-left:0;padding-top:0;padding-bottom:0;text-align:center;">
          <a href="{{ env('APP_FRONT_URL') }}" class="author" style="display:inline-block;margin-top:24px;margin-bottom:50px;margin-right:0;margin-left:0;">
            <img src="{{ env('APP_FRONT_URL') }}static/img/mailing/brand_footer.jpg" alt="Prilov.com" class="img">
          </a>
        </td>
      </tr>
      <!-- Footer -->
      @include('email.template_footer')
      <!-- end Footer -->
    </table>
  </div>
</body>

</html>

