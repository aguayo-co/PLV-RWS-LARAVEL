<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <thead>
    <tr>
      <th colspan="2" class="table__header uppercase" style="text-transform:uppercase;font-size:18px;padding-top:16px;padding-bottom:16px;padding-right:0;padding-left:0;border-bottom-width:1px;border-bottom-style:solid;border-bottom-color:#000;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">Resumen de compra</th>
    </tr>
  </thead> 
  <tfoot class="table__footer">
    <tr>
      <td align="left" class="spacing_sub-cell table__footer-cell">Total de tu orden:</td>
      @if ($order->active_payment->gateway === 'PayU')
      <td align="right" class="spacing_sub-cell table__footer-cell">${{ number_format((int) ($order->active_payment->total * (100 + config('prilov.payments.percentage_fee.pay_u')) / 100), 0, ',', '.') }}</td>
      @elseif ($order->active_payment->gateway === 'MercadoPago')
      <td align="right" class="spacing_sub-cell table__footer-cell">${{ number_format((int) ($order->active_payment->total * (100 + config('prilov.payments.percentage_fee.mercado_pago')) / 100), 0, ',', '.') }}</td>
      @else
      <td align="right" class="spacing_sub-cell table__footer-cell">${{ number_format($order->due, 0, ',', '.') }}</td>
      @endif
    </tr>
  </tfoot>                       
  <tbody>
    <!-- Por cada Sale de Order -->
    @foreach ($order->sales as $sale)
    <tr>
      <td colspan="2">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
          <tbody>
            <tr>
              <td align="center" style="padding-top:12px;padding-right:0;padding-left:0;font-size:16px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;font-weight:bold;color:#707070;">
                <a href="{{ env('APP_FRONT_URL') }}closet/{{ $sale->user->id }}" style="color:#707070; text-decoration: none;"><img src="{{ $sale->user->picture }}" alt="" width="40" class="v-align_middle" style="display:inline-block;vertical-align:middle;border: 3px solid #f65a66;width: 45px;height: 45px;border-radius: 100px;margin-right: 10px;"></a>
                <a href="{{ env('APP_FRONT_URL') }}closet/{{ $sale->user->id }}" style="color:#707070; text-decoration: none;"><span class="v-align_middle" style="display:inline-block;vertical-align:middle;">{{ $sale->user->first_name }} {{ $sale->user->last_name }}<br><span style="font-size: 11px;">Teléfono: {{$sale->user->phone}}</span></span></a>
              </td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
    @foreach ($sale->products as $product)
    <tr>
      <td colspan="2" class="@if ($loop->first && $loop->count > 1) spacing_head-cell @else spacing_sub-cell @endif" style="padding-top:20px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
          <thead>
              <tr>
                <th align="left" class="sub-thead" style="font-size:16px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;"><a href="{{ env('APP_FRONT_URL') }}producto/{{ $product->slug }}__{{$product->id}}" style="color:#000; text-decoration: none;">{{ ucfirst($product->title) }}</a></th>
                <th align="right" class="sub-thead" style="font-size:16px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">${{ number_format($product->sale_price, 0, ',', '.') }}</th>
              </tr>
          </thead>
          <tbody>
            <tr>
              <td align="left" class="sub-cell" style="padding-top:6px;font-size:12px;color:#9B9B9B;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">@if ($product->sale_price !== $product->price) Precio original @endif</td>
              <td align="right" class="sub-cell" style="padding-top:6px;font-size:12px;color:#9B9B9B;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">@if ($product->sale_price !== $product->price) ${{ number_format($product->price, 0, ',', '.') }} @endif</td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
    @endforeach
    <tr>
      <td colspan="2" class="spacing_sub-cell spacing_foot-cell" style="padding-top:20px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
          <thead>
            <tr>
              <th align="left" class="sub-thead" style="font-size:16px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">Envío</th>
              <th align="right" class="sub-thead" style="font-size:16px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">@if ($sale->shipping_cost > 0) ${{number_format($sale->shipping_cost, 0, ',', '.')}} @else N/A @endif</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td align="left" class="sub-cell" style="padding-top:6px;font-size:12px;color:#9B9B9B;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">{{$sale->shippingMethod->name}}</td>
              <td align="right" class="sub-cell" style="padding-top:6px;font-size:12px;color:#9B9B9B;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;"></td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
    @endforeach
    <tr>
      <td colspan="2" class="spacing_sub-cell">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
          <thead>
            <tr>
              <th align="left" class="sub-thead" style="font-size:16px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">Subtotal de la orden</th>
              <th align="right" class="sub-thead" style="font-size:16px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4">${{ number_format($order->total + $order->shipping_cost, 0, ',', '.') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td align="left" class="sub-cell" style="padding-top:6px;font-size:12px;color:#9B9B9B;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">Valor antes de descuentos y recargos</td>
              <td align="right" class="sub-cell" style="padding-top:6px;font-size:12px;color:#9B9B9B;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;"></td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
    @if ($order->active_payment->gateway === 'PayU' || $order->active_payment->gateway === 'MercadoPago')
    <tr>
      <td colspan="2" class="spacing_sub-cell">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
          <thead>
            <tr>
              <th align="left" class="sub-thead" style="font-size:16px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">Comisión {{ $order->active_payment->gateway }}</th>
              <th align="right" class="sub-thead" style="font-size:16px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4">+${{ number_format($order->active_payment->total * 0.05, 0, ',', '.') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td align="left" class="sub-cell" style="padding-top:6px;font-size:12px;color:#9B9B9B;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">(5%)</td>
              <td align="right" class="sub-cell" style="padding-top:6px;font-size:12px;color:#9B9B9B;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;"></td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
    @endif
    @if ($order->used_credits > 0)
    <tr>
      <td colspan="2" class="spacing_sub-cell">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
          <thead>
            <tr>
              <th align="left" class="sub-thead" style="font-size:16px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">Créditos Prilov</th>
              <th align="right" class="sub-thead" style="font-size:16px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;color:#f65a66">-{{ number_format($order->used_credits, 0, ',', '.') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td align="left" class="sub-cell" style="padding-top:6px;font-size:12px;color:#9B9B9B;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">Tus créditos usados en esta compra</td>
              <td align="right" class="sub-cell" style="padding-top:6px;font-size:12px;color:#9B9B9B;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;"></td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
    @endif
    
    <tr>
      <td colspan="2" class="spacing_sub-cell spacing_foot-cell" style="border-bottom: 1px solid #000000;">
        @if ($order->coupon_id)
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
          <thead>
            <tr>
              <th align="left" class="sub-thead" style="font-size:16px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">Código de descuento</th>
              <th align="right" class="sub-thead" style="font-size:16px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;color:#f65a66">@if ($order->coupon_id) -${{ number_format(data_get($order->applied_coupon, 'discount'), 0, ',', '.') }} @else N/A @endif</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td align="left" class="sub-cell" style="padding-top:6px;font-size:12px;color:#9B9B9B;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">@if ($order->coupon_id) ({{$order->coupon->code}}) @endif</td>
              <td align="right" class="sub-cell" style="padding-top:6px;font-size:12px;color:#9B9B9B;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;"></td>
            </tr>
          </tbody>
        </table>
        @endif
      </td>
    </tr>
  </tbody>
</table>