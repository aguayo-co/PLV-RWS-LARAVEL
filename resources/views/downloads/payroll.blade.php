@php
  echo '<?xml version="1.0"?>'
@endphp
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
  xmlns:o="urn:schemas-microsoft-com:office:office"
  xmlns:x="urn:schemas-microsoft-com:office:excel"
  xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
  xmlns:html="http://www.w3.org/TR/REC-html40">
<Worksheet ss:Name="Payroll-{{ $payroll->id }} Transfers">
    <Table>
      <Row>
        <Cell><Data ss:Type="String">Nº Cuenta de Cargo</Data></Cell>
        <Cell><Data ss:Type="String">Nº Cuenta de Destino</Data></Cell>
        <Cell><Data ss:Type="String">Banco Destino</Data></Cell>
        <Cell><Data ss:Type="String">Rut Beneficiario</Data></Cell>
        <Cell><Data ss:Type="String">Dig. Verif. Beneficiario</Data></Cell>
        <Cell><Data ss:Type="String">Nombre Beneficiario</Data></Cell>
        <Cell><Data ss:Type="String">Monto Transferencia</Data></Cell>
        <Cell><Data ss:Type="String">Nro.Factura Boleta (1)</Data></Cell>
        <Cell><Data ss:Type="String">Nº Orden de Compra(1)</Data></Cell>
        <Cell><Data ss:Type="String">Tipo de Pago(2)</Data></Cell>
        <Cell><Data ss:Type="String">Mensaje Destinatario (3)</Data></Cell>
        <Cell><Data ss:Type="String">Email Destinatario(3)</Data></Cell>
        <Cell><Data ss:Type="String">Cuenta Destino inscrita como(4)</Data></Cell>
        <Cell><Data ss:Type="String">Monto Boleta</Data></Cell>
      </Row>
      @foreach ($transfers as $transfer)<Row>
        <Cell><Data ss:Type="String">61649236</Data></Cell>
        <Cell><Data ss:Type="String">{{ data_get($transfer, 'accountNumber') }}</Data></Cell>
        <Cell><Data ss:Type="String">{{ data_get($transfer, 'bankId') }}</Data></Cell>
        <Cell><Data ss:Type="String">{{ data_get($transfer, 'rut.0') }}</Data></Cell>
        <Cell><Data ss:Type="String">{{ data_get($transfer, 'rut.1') }}</Data></Cell>
        <Cell><Data ss:Type="String">{{ data_get($transfer, 'fullName') }}</Data></Cell>
        <Cell><Data ss:Type="Number">{{ data_get($transfer, 'amount') }}</Data></Cell>
        <Cell><Data ss:Type="String"></Data></Cell>
        <Cell><Data ss:Type="String"></Data></Cell>
        <Cell><Data ss:Type="String">REM</Data></Cell>
        <Cell><Data ss:Type="String">Creditos Prilov</Data></Cell>
        <Cell><Data ss:Type="String">{{ data_get($transfer, 'email') }}</Data></Cell>
        <Cell><Data ss:Type="String">{{ data_get($transfer, 'fullName') }}</Data></Cell>
        <Cell><Data ss:Type="String">{{ data_get($transfer, 'commission') }}</Data></Cell>
      </Row>
    @endforeach</Table>
  </Worksheet>
</Workbook>
