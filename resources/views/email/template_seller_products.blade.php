@php
  $earnings = 0;

  // Seller earnings calculation
  foreach ($sale->products as $product) {
    //Product had disscount
    if ($product->sale_price !== $product->price) {
      $earnings += (int) ($product->sale_price - ($product->price * $product->commission / 100));
    }
    else {
      $earnings += (int) ($product->price - ($product->price * $product->commission / 100));
    }
  }
@endphp
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <thead>
    <tr>
      <th colspan="2" class="table__header uppercase" style="text-transform:uppercase;font-size:18px;padding-top:16px;padding-bottom:16px;padding-right:0;padding-left:0;border-bottom-width:1px;border-bottom-style:solid;border-bottom-color:#000;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">Productos que vendiste</th>
    </tr>
  </thead> 
  <tfoot class="table__footer">
    <tr>
      <td align="left" class="spacing_sub-cell table__footer-cell">Total ganado en esta venta:</td>
      <td align="right" class="spacing_sub-cell table__footer-cell">${{ number_format($earnings, 0, ',', '.') }}</td>
    </tr>
  </tfoot>                       
  <tbody>
    @foreach ($sale->products as $product)
    <tr>
      <td colspan="2" class="@if ($loop->first && $loop->count > 1) spacing_head-cell @elseif ($loop->last) spacing_sub-cell spacing_foot-cell @else spacing_sub-cell @endif" style="padding-top:20px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
          <thead>
              <tr>
                <th align="left" class="sub-thead" style="font-size:16px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;"><a href="{{ env('APP_FRONT_URL') }}producto/{{ $product->slug }}__{{$product->id}}" style="color:#000; text-decoration: none;">{{ ucfirst($product->title) }}</a></th>
                <th align="right" class="sub-thead" style="font-size:16px;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">${{ number_format($product->price, 0, ',', '.') }}</th>
              </tr>
          </thead>
          <tbody>
            <tr>
              <td align="left" class="sub-cell" style="padding-top:6px;font-size:12px;color:#9B9B9B;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">Comisión Prilov ({{ $product->commission }}%)</td>
              <td align="right" class="sub-cell" style="padding-top:6px;font-size:12px;color:#f65a66;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">-${{ number_format((int)($product->price * $product->commission / 100) , 0, ',', '.') }}</td>
            </tr>
          </tbody>
          @if ($product->sale_price !== $product->price)
          <tbody>
            <tr>
              <td align="left" class="sub-cell" style="padding-top:6px;font-size:12px;color:#9B9B9B;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">Campaña de descuento ({{ (int) ($product->sale_price * 100 / $product->price) }}%)</td>
              <td align="right" class="sub-cell" style="padding-top:6px;font-size:12px;color:#f65a66;font-family:'Montserrat', Arial, Helvetica, sans-serif;line-height:1.4;">-${{ number_format((int)($product->price - $product->sale_price) , 0, ',', '.') }}</td>
            </tr>
          </tbody>
          @endif
        </table>
      </td>
    </tr>
    @endforeach
  </tbody>
</table>