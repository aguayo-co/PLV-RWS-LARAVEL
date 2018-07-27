<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:300,500,600,700|Roboto+Condensed:400,700|Roboto:100,400'">
  <title>Médio de pago automático - confirmación ChileExpress</title>

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
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
      line-height: 1.4;
      background-color: #F6F6F6;
    }

    p {
      margin: 0;
    }

    .row {
      width: 100%;
    }

    .table_main {
      margin: 0 auto;
      background-color: #F6F6F6;
    }

    .table {
      border-collapse: collapse;
      -ms-text-size-adjust: 100%;
      -webkit-text-size-adjust: 100%;
      background-color: #ffffff;
    }

    .table__small {
      max-width: 460px;
      min-width: 350px;
      border-collapse: collapse;
      -ms-text-size-adjust: 100%;
      -webkit-text-size-adjust: 100%;
    }

    .table__medium {
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
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
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

    .img_brand {
      width: 200px;
    }

    .title {
      display: inline-block;
      font-size: 30px;
      font-weight: bold;
      border-bottom: 1px solid #979797;
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
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
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
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

    .uppercase {
      text-transform: uppercase;
    }

    .txt-bold {
      font-weight: bold;
    }

    .table-prefooter__spacing-cell {
      padding: 10px 0;
    }

    .table-prefooter__cell {
      font-size: 18px;
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
      line-height: 1.4;
    }

    .spacing_table {
      padding: 30px 0 40px;
    }

    .table__header {
      font-size: 18px;
      padding: 16px 0;
      border-bottom: 1px solid #000;
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
      line-height: 1.4;
    }

    .spacing_head-cell {
      padding-top: 20px;
    }

    .spacing_foot-cell {
      padding-bottom: 12px;
      border-bottom: 1px solid #EFEFEF;
    }

    .sub-thead {
      font-size: 16px;
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
      line-height: 1.4;
    }

    .sub-cell {
      padding-top: 6px;
      font-size: 12px;
      color: #9B9B9B;
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
      line-height: 1.4;
    }

    .spacing_sub-cell {
      padding-top: 12px;
    }

    .table__footer-cell {
      font-size: 18px;
      font-weight: bold;
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
      line-height: 1.4;
    }

    .head-table_second {
      padding-bottom: 6px;
      font-size: 16px;
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
      line-height: 1.4;
    }

    .txt-table_second {
      padding: 12px 0 30px;
      font-size: 16px;
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
      line-height: 1.4;
    }

    .txt-table_second-grey {
      font-weight: bold;
      color: #707070;
    }

    .bb_light {
      border-bottom: 1px solid #EFEFEF;
    }

    .v-align_middle {
      display: inline-block;
      vertical-align: middle;
    }

    .cell_footer {
      background-color: #F6F6F6;
    }

    .footer__title {
      padding: 20px 0;
      font-size: 16px;
      font-weight: bold;
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
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
      font-family: 'Montserrat', Arial, Helvetica, sans-serif;
      line-height: 1.4;
    }

  </style>
</head>

<body style="font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;background-color:#F6F6F6;">
  <table class="table_main" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:0;margin-bottom:0;margin-right:auto;margin-left:auto;background-color:#F6F6F6;">
    <tbody>
      <tr>
        <td align="center">
          <table class="table" width="600" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;background-color:#ffffff;">
            <tbody>
              <!-- Header -->
              @include('email.template_header')
              <!-- end Header -->
              <!-- Body -->
              <tr class="row" style="width:100%;">
                <td class="cell" align="center" style="padding-right:0;padding-left:0;padding-top:0;padding-bottom:0;">
                  <table class="table__small" cellpadding="0" cellspacing="0" border="0" style="max-width:460px;min-width:350px;border-collapse:collapse;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;">
                    <tr class="row" style="width:100%;">
                      <td class="cell spacing_title center uppercase" style="padding-top:40px;padding-bottom:10px;padding-right:20px;padding-left:20px;text-align:center;text-transform:uppercase;">
                        <p class="title" style="margin-top:0;margin-bottom:0;margin-right:0;margin-left:0;display:inline-block;font-size:30px;font-weight:bold;border-bottom-width:1px;border-bottom-style:solid;border-bottom-color:#979797;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">Hola
                          <span class="highlight" style="color:#f65a66;">{{ $user->first_name }}</span>
                        </p>
                      </td>
                    </tr>
                    <tr class="row" style="width:100%;">
                      <td class="cell spacing txt highlight uppercase" align="center" style="padding-top:22px;padding-bottom:0;padding-right:20px;padding-left:20px;margin-top:0;margin-bottom:0;margin-right:0;margin-left:0;font-size:18px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;color:#f65a66;text-transform:auppercase;">¡Gracias por subir tu comprobante!<br>Tu compra ya fue confirmada.</td>
                    </tr>
                    <tr class="row" style="width:100%;">
                      <td class="cell spacing_table" align="center" style="padding-top:30px;padding-bottom:40px;padding-right:0;padding-left:0;">
                        <!-- Resumen de compra -->
                        @include('email.template_receipt', ['order' => $order])
                        <!-- end Resumen de compra -->
                      </td>
                    </tr>
                    <!-- aqui van las tablas -->
                  </table>
                </td>
              </tr>
              <tr class="row" style="width:100%;">
                <td align="center">
                  <table class="table__pre-footer" width="500" cellpadding="0" cellspacing="0" border="0">
                    <tbody>
                      <tr class="row" style="width:100%;">
                        <td class="cell" style="padding-right:0;padding-left:0;padding-top:0;padding-bottom:0;">
                          <a href="{{ env('APP_FRONT_URL') }}user/tus-compras" class="btn btn_solid" style="display:block;width:70%;max-width:410px;padding-top:18px;padding-bottom:18px;padding-right:6px;padding-left:6px;margin-top:0;margin-bottom:20px;margin-right:auto;margin-left:auto;border-width:2px;border-style:solid;border-color:#000000;text-decoration:none;font-size:14px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;text-align:center;background-color:#000000;color:#ffffff;">Ir a Tus Compras</a>
                        </td>
                      </tr>
                      <tr>
                        <td class="table-prefooter__cell table-prefooter__spacing-cell" align="center" style="padding-top:10px;padding-bottom:10px;padding-right:0;padding-left:0;font-size:18px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">
                          Como elegiste acordar el envío con
                          @if (count($order->sales) > 1 )
                          las vendedoras te recomendamos contactarlas para confirmar el lugar de la entrega o de envío ;)
                          <br>
                          <br>
                          Las vendedoras también recibieron un email con tus datos.
                          @else
                          la vendedora te recomendamos contactarla para confirmar el lugar de la entrega o de envío ;)
                          <br>
                          <br>
                          La vendedora también recibió un email con tus datos.
                          @endif
                          <br>
                          <br>¡Ojalá te encante tu nueva joyita!
                        </td>
                      </tr>
                      <tr class="row" style="width:100%;">
                        <td class="cell spacing txt highlight" align="center" style="padding-top:22px;padding-bottom:0;padding-right:20px;padding-left:20px;margin-top:0;margin-bottom:0;margin-right:0;margin-left:0;color:#f65a66;font-size:18px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">Kisses!</td>
                      </tr>
                      <tr class="row" style="width:100%;">
                        <td class="cell center txt spacing_pre-footer" style="padding-top:40px;padding-bottom:40px;padding-right:20px;padding-left:20px;text-align:center;font-size:18px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">
                          <a href="{{ env('APP_FRONT_URL') }}" class="author" style="display:inline-block;">
                            <img src="{{ env('APP_FRONT_URL') }}static/img/mailing/brand_footer.jpg" alt="Prilov.com" class="img author__img" style="width:100px;">
                          </a>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </td>
              </tr>
              <!-- end Body -->
              <!-- Footer -->
              @include('email.template_footer')
              <!-- end Footer -->
            </tbody>
          </table>
        </td>
      </tr>
    </tbody>
  </table>
</body>

</html>
