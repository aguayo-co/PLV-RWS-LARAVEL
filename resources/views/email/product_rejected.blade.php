<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:300,500,600,700|Roboto+Condensed:400,700|Roboto:100,400'">
  <title>Agregar producto</title>

  <style>
    @font-face {
      font-family: 'Montserrat', arial;
      font-style: normal;
      font-weight: 300;
      src: local('Montserrat Light'), local('Montserrat-Light'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_cJD3gnD_vx3rCs.woff) format('woff');
      unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
    }


    @font-face {
      font-family: 'Montserrat', arial;
      font-style: normal;
      font-weight: 500;
      src: local('Montserrat Medium'), local('Montserrat-Medium'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_ZpC3gnD_vx3rCs.woff) format('woff');
      unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
    }


    @font-face {
      font-family: 'Montserrat', arial;
      font-style: normal;
      font-weight: 600;
      src: local('Montserrat SemiBold'), local('Montserrat-SemiBold'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_bZF3gnD_vx3rCs.woff) format('woff');
      unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
    }


    @font-face {
      font-family: 'Montserrat', arial;
      font-style: normal;
      font-weight: 700;
      src: local('Montserrat Bold'), local('Montserrat-Bold'), url(https://fonts.gstatic.com/s/montserrat/v12/JTURjIg1_i6t8kCHKm45_dJE3gnD_vx3rCs.woff) format('woff');
      unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
    }

    body {
      background-color: #ffffff;
    }

    p {
      margin: 0;
    }

    .row {
      width: 100%;
    }

    .table_main {
      margin: 0 auto;
      background-color: #ffffff;
    }

    .table {
      border-collapse: collapse;
      -ms-text-size-adjust: 100%;
      -webkit-text-size-adjust: 100%;
      background-color: #ffffff;
    }

    .table__small {
      max-width: 460px;
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

    .cell__txt {
      font-size: 20px;
      font-family: 'Montserrat', arial;
      line-height: 1.4;
    }

    .spacing {
      padding: 22px 20px 0;
      margin: 0;
    }

    .spacing_title {
      padding: 40px 20px 10px;
    }

    .spacing_pre-footer {
      padding: 40px 20px;
    }

    .header {
      display: inline-block;
      padding: 20px 0 10px;
    }

    .img {
      width: 100%;
    }

    .img_brand {
      width: 200px;
    }

    .title {
      display: inline-block;
      font-size: 30px;
      font-weight: bold;
      border-bottom: 1px solid #979797;
      font-family: 'Montserrat', arial;
      line-height: 1.4;
    }

    .highlight {
      color: #f65a66;
    }

    .center {
      text-align: center;
    }

    .txt {
      font-size: 18px;
      font-family: 'Montserrat', arial;
      line-height: 1.4;
    }

    .anchor {
      color: #000000;
      border-bottom: 2px solid #f65a66;
      text-decoration: none;
      text-transform: none;
    }

    .author {
      display: inline-block;
    }

    .author__img {
      width: 100px;
    }

    .card {
      line-height: 1;
      font-size: 0px;
    }

    .card__caption {
      font-weight: bold;
      padding: 20px 0;
      font-size: 16px;
      font-family: 'Montserrat', arial;
      line-height: 1.4;
      border-bottom: 1px solid #000000;
    }

    .cell_footer {
      background-color: #F6F6F6;
    }

    .footer__title {
      padding: 20px 0;
      font-size: 16px;
      font-weight: bold;
      font-family: 'Montserrat', arial;
      line-height: 1.4;
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
      padding-bottom: 20px;
      text-align: center;
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

<body style="background-color:#ffffff;">
  <table class="table_main" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;background-color:#ffffff;">
    <tbody>
      <tr>
        <td align="center">
          <table class="table" width="600" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;background-color:#ffffff;">
            <tbody>
              <!-- Header -->
              <tr class="row" style="width:100%;">
                <td class="cell cell_header" style="padding-right:0;padding-left:0;padding-top:0;padding-bottom:0;text-align:center;background-color:#000000;">
                  <a href="{{ env('APP_FRONT_URL') }}" class="header" style="display:inline-block;padding-top:20px;padding-bottom:10px;padding-right:0;padding-left:0;">
                    <img src="{{ env('APP_FRONT_URL') }}static/img/mailing/brand.jpg" alt="Prilov.com" class="img img_brand" style="width:200px;">
                  </a>
                </td>
              </tr>
              <!-- end Header -->
              <!-- Body -->
              <tr class="row" style="width:100%;">
                <td class="cell" align="center" style="padding-right:0;padding-left:0;padding-top:0;padding-bottom:0;">
                  <table class="table__small" style="max-width:460px;border-collapse:collapse;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;">
                    <tr class="row" style="width:100%;">
                      <td class="cell spacing_title txt" style="padding-top:40px;padding-bottom:10px;padding-right:20px;padding-left:20px;font-size:18px;font-family:'Montserrat', arial;line-height:1.4;">
                        Hola
                        <span class="highlight" style="color:#f65a66;">{{ $user->first_name }}</span>,
                        <br> Tu producto:
                      </td>
                    </tr>
                    <!-- <tr class="row" style="width:100%;" >
                      <td class="cell spacing txt" style="padding-top:22px;padding-bottom:0;padding-right:20px;padding-left:20px;margin-top:0;margin-bottom:0;margin-right:0;margin-left:0;font-size:18px;font-family:'Montserrat', arial;line-height:1.4;" >Acabas de agregar: </td>
                    </tr> -->
                    <tr class="row" style="width:100%;">
                      <td class="cell spacing txt" align="center" style="padding-top:22px;padding-bottom:0;padding-right:20px;padding-left:20px;margin-top:0;margin-bottom:0;margin-right:0;margin-left:0;font-size:18px;font-family:'Montserrat', arial;line-height:1.4;">
                        <table width="240" cellpadding="0" cellspacing="0" border="0">
                          <tbody>
                            <tr>
                              <td class="card" style="line-height:1;font-size:0px;">
                                <img src="{{ $product->images[0] }}" alt="{{ $product->title }}" class="img" style="width:100%;">
                              </td>
                            </tr>
                            <tr>
                              <td class="card__caption" style="font-weight:bold;padding-top:20px;padding-bottom:20px;padding-right:0;padding-left:0;font-size:16px;font-family:'Montserrat', arial;line-height:1.4;border-bottom-width:1px;border-bottom-style:solid;border-bottom-color:#000000;">
                                {{ $product->title }}
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </td>
                    </tr>
                    <tr class="row" style="width:100%;">
                      <td class="cell spacing txt" style="padding-top:22px;padding-bottom:0;padding-right:20px;padding-left:20px;margin-top:0;margin-bottom:0;margin-right:0;margin-left:0;font-size:18px;font-family:'Montserrat', arial;line-height:1.4;">Fue rechazado porque "{{ $product->admin_notes }}"</td>
                    </tr>
                    <tr class="row" style="width:100%;">
                      <td class="cell spacing txt" style="padding-top:22px;padding-bottom:0;padding-right:20px;padding-left:20px;margin-top:0;margin-bottom:0;margin-right:0;margin-left:0;font-size:18px;font-family:'Montserrat', arial;line-height:1.4;">Te invitamos a hacer este cambio para que tu producto sea publicado lo antes posible.</td>
                    </tr>
                    <tr class="row" style="width:100%;">
                      <td class="cell spacing txt" style="padding-top:22px;padding-bottom:0;padding-right:20px;padding-left:20px;margin-top:0;margin-bottom:0;margin-right:0;margin-left:0;font-size:18px;font-family:'Montserrat', arial;line-height:1.4;">
                         <a href="{{ env('APP_FRONT_URL') }}editar-producto/{{ $product->id }}" class="link nav__link" style="display:inline-block;margin-top:5px;margin-bottom:5px;margin-right:5px;margin-left:5px;text-align:center;color:#000000;border-bottom-width:2px;border-bottom-style:solid;border-bottom-color:#f65a66;text-decoration:none;font-size:14px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;padding-top:30px;">Editar producto</a>
                      </td>
                    </tr>
                    <tr class="row" style="width:100%;">
                      <td class="cell spacing txt highlight" align="center" style="padding-top:22px;padding-bottom:0;padding-right:20px;padding-left:20px;margin-top:0;margin-bottom:0;margin-right:0;margin-left:0;color:#f65a66;font-size:18px;font-family:'Montserrat', arial;line-height:1.4;">Kisses!</td>
                    </tr>
                    <tr class="row" style="width:100%;">
                      <td class="cell center txt spacing_pre-footer" style="padding-top:40px;padding-bottom:40px;padding-right:20px;padding-left:20px;text-align:center;font-size:18px;font-family:'Montserrat', arial;line-height:1.4;">
                        <a href="{{ env('APP_FRONT_URL') }}" class="author" style="display:inline-block;">
                          <img src="{{ env('APP_FRONT_URL') }}static/img/mailing/brand_footer.jpg" alt="Prilov.com" class="img author__img" style="width:100px;">
                        </a>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <!-- end Body -->
              <!-- Footer -->
              <tr class="row" style="width:100%;">
                <td class="cell cell_footer center" style="padding-right:0;padding-left:0;padding-top:0;padding-bottom:0;text-align:center;background-color:#F6F6F6;">
                  <table width="600" cellpadding="0" cellspacing="0" border="0">
                    <tbody>
                      <tr>
                        <td class="footer__title" style="padding-top:20px;padding-bottom:20px;padding-right:0;padding-left:0;font-size:16px;font-weight:bold;font-family:'Montserrat', arial;line-height:1.4;">Síguenos</td>
                      </tr>
                      <tr>
                        <td>
                          <a href="https://www.instagram.com/prilovchile/?hl=es-la" class="footer__link" title="Síguenos en Instragram" style="display:inline-block;padding-top:0;padding-bottom:0;padding-right:16px;padding-left:16px;">
                            <img src="{{ env('APP_FRONT_URL') }}static/img/mailing/instagram.jpg" alt="Instragram" class="img" style="width:100%;">
                          </a>
                          <a href="https://twitter.com/prilovchile?lang=es" class="footer__link" title="Síguenos en Twitter" style="display:inline-block;padding-top:0;padding-bottom:0;padding-right:16px;padding-left:16px;">
                            <img src="{{ env('APP_FRONT_URL') }}static/img/mailing/twitter.jpg" alt="Twitter" class="img" style="width:100%;">
                          </a>
                          <a href="https://www.facebook.com/prilovchile" class="footer__link" title="Síguenos en Facebook" style="display:inline-block;padding-top:0;padding-bottom:0;padding-right:16px;padding-left:16px;">
                            <img src="{{ env('APP_FRONT_URL') }}static/img/mailing/facebook.jpg" alt="Facebook" class="img" style="width:100%;">
                          </a>
                        </td>
                      </tr>
                      <tr>
                        <td class="footer__txt" style="padding-top:20px;padding-bottom:20px;padding-right:20px;padding-left:20px;margin-top:0;margin-bottom:0;margin-right:0;margin-left:0;font-size:14px;font-family:'Montserrat', arial;line-height:1.4;">
                          Copyright © 2017 prilov.com. Todos los derechos reservados.
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </td>
              </tr>
              <!-- end Footer -->
            </tbody>
          </table>
        </td>
      </tr>
    </tbody>
  </table>
</body>

</html>

