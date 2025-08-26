<?php

function LogoPDfDescarga($request, $response, $args) {
    
    try {
        $pdf = new FPDF();
        $pdf->AddPage();

        $imageRuta = './logo/logo.png';

        list($width, $height) = getimagesize($imageRuta);

        $pdfWidth = $pdf->GetPageWidth();
        $pdfHeight = $pdf->GetPageHeight();
        $imageWidth = 150;
        $imageHeight = ($height / $width) * $imageWidth;

        
        $x = ($pdfWidth - $imageWidth) / 2;
        $y = ($pdfHeight - $imageHeight) / 2;

        
        $pdf->Image($imageRuta, $x, $y, $imageWidth, $imageHeight);

        $response = $response->withHeader('Content-Type', 'application/pdf')->withHeader('Content-Disposition', 'attachment; filename="logo.pdf"');

        $pdf->Output('D');

        return $response;

    } catch (Exception $e) {
        $response->getBody()->write("Error: " . $e->getMessage());
        return $response->withHeader('Content-Type', 'application/json');
    }
}