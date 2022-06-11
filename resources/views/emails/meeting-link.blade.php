<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>IWC Virtual Tour Invitation</title>
    <!--[if mso]>
 <noscript>
  <xml>
            <o:OfficeDocumentSettings>
    <o:PixelsPerInch>96</o:PixelsPerInch>
   </o:OfficeDocumentSettings>
  </xml>
 </noscript>
 <![endif]-->
    <style>
        @font-face {
            font-family: 'Gotham';
            src: url('https://amsapi.net/xrcentral/assets/fonts/Gotham-Bold.eot');
            src: url('https://amsapi.net/xrcentral/assets/fonts/Gotham-Bold.eot?#iefix') format('embedded-opentype'),
                url('https://amsapi.net/xrcentral/assets/fonts/Gotham-Bold.woff2') format('woff2'),
                url('https://amsapi.net/xrcentral/assets/fonts/Gotham-Bold.woff') format('woff'),
                url('https://amsapi.net/xrcentral/assets/fonts/Gotham-Bold.ttf') format('truetype'),
                url('https://amsapi.net/xrcentral/assets/fonts/Gotham-Bold.svg#Gotham-Bold') format('svg');
            font-weight: bold;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'Gotham';
            src: url('https://amsapi.net/xrcentral/assets/fonts/Gotham-Medium.eot');
            src: url('https://amsapi.net/xrcentral/assets/fonts/Gotham-Medium.eot?#iefix') format('embedded-opentype'),
                url('https://amsapi.net/xrcentral/assets/fonts/Gotham-Medium.woff2') format('woff2'),
                url('https://amsapi.net/xrcentral/assets/fonts/Gotham-Medium.woff') format('woff'),
                url('https://amsapi.net/xrcentral/assets/fonts/Gotham-Medium.ttf') format('truetype'),
                url('https://amsapi.net/xrcentral/assets/fonts/Gotham-Medium.svg#Gotham-Medium') format('svg');
            font-weight: 500;
            font-style: normal;
            font-display: swap;
        }

        table,
        td,
        div,
        h1,
        p,
        input {
            font-family: 'Gotham';
            border-collapse: collapse;
            border: 0;
            border-spacing: 0;
        }

    </style>

<body style="margin:0;padding:0;">
    <table role="presentation" style="width:100%;background:#ffffff;">
        <tr>
            <td align="center" style="padding:30px;">
                <table role="presentation" style="min-width:320px;max-width:960px;text-align:left;">
                    <tr>
                        <td style="padding:0;">
                            <img src="https://amsapi.net/xrcentral/assets/images/iwc_invitation_header.png" alt=""
                                style="width:100%;height:auto;display:block;" />
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0" align="center">
                            <br><br>
                            <h1 style="font-weight: bold;letter-spacing: 1px;">CONFIRMED<br>IWC VIRTUAL MANUFACTURE TOUR</h1>
                            <br>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0;" align="center">
                            <table role="presentation"
                                style="width:80%;border-collapse:collapse;border:0;border-spacing:0;">
                                <tr>
                                    <td align="center">
                                        <p style="font-size:large;font-weight: 500;line-height: 150%;">
                                            Congratulations your Virtual Tour has been confirmed. To enhance your experience we kindly invite you to switch on your camera during the tour.
                                            <br><br>
                                            If you have any questions before your tour, please reach out to <a href="mailto:visit@iwc.com">visit@iwc.com</a>
                                            <br><br><br>
                                            DATE: {{ $meeting_date }}<br>TIME: {{ $start_time }} (CET)
                                            <br><br><br>
                                            <a href="{{ $meeting_link }}">
                                                <input type="button" value="JOIN"
                                                    style="font-size:large;padding:15px 50px 15px 50px;background-color:#000000;color:#ffffff;border: 0;" />
                                            </a>
                                            <br><br>
                                            <span style="font-weight: bold;">
                                                MEETING ID: {{ $meeting_id }}<br>
                                                PASSCODE: {{ $password }}
                                            </span>
                                            <br><br>
                                            <span style="color: #666666;">LINK: </span><a
                                                href="{{ $meeting_link }}">{{ $meeting_link }}</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0;" align="center">
                            <img style="margin-top:50px;height:60px;width:auto;"
                                src="https://amsapi.net/xrcentral/assets/images/logo-iwc-sml-black.png" />
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</head>

</html>
