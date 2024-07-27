<?php

namespace App\Http\Controllers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;

class MailerController extends Controller
{
    public function test()
    {
        return response()->json([
            'message' => 'ok',
        ], 200);
    }
    public function index(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'message' => 'required|string|min:10',
            'token' => 'required|string',
            'secret_key' => 'required|string',
            'mail_username' => 'required|string',
            'mail_password' => 'required|string',
            'mail_host' => 'required|string',
            'mail_port' => 'required|string',
            'mail_encryption' => 'required|string',
            'company' => 'required|string',
            'file' => 'nullable|file|max:2048',
        ]);

        $validated = $validator->validated();

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error en el formulario',
                'errors' => $validator->errors()
            ], 500);
        }

        $credentials = [
            'mail_username' => $validated['mail_username'],
            'mail_password' => $validated['mail_password'],
            'mail_host' => $validated['mail_host'],
            'mail_port' => $validated['mail_port'],
            'mail_encryption' => $validated['mail_encryption'],
            'company' => $validated['company'],
        ];
        $title = 'Contacto desde la web - de:'.$validated['name'];
        $data = [
            'name' => $validated['name'],
            'message' => $validated['message'],
            'title' => $title
        ];
        $email = $validated['email'];
        $phone = $validated['phone'];
        $file =  $request->hasFile('file') ? $request->file('file') : null;

        // $endpoint = 'https://www.recaptcha.net/recaptcha/api/siteverify';

        // $response = Http::asForm()->post($endpoint, [
        //     'secret' => $validated['secret_key'],
        //     'response' => $validated['token'],
        // ])->json();

        // if( $response['success'] && $response['score'] > 0.5) {
            $this->send($credentials, $data, $email, $phone, $file );
        // }else{
        //     return response()->json([
        //         'message' => 'Error en el captcha',
        //         'response' => $response
        //     ], 500);
        // }



    }

    public function send($credentials, $data, $email = null, $phone = null, UploadedFile $file)
    {

        //datos de SMPT
        $emailUserName = $credentials['mail_username'];
        $emailPassword = $credentials['mail_password'];
        $smtpHost = $credentials['mail_host'];
        $smtpPort = $credentials['mail_port'];
        $smtpEncryption = $credentials['mail_encryption'];
        $company = $credentials['company'];
        //datos de envío
        $name =$data['name'];
        $message = $data['message'];
        $title = $data['title'];
        //para el adjunto
        $attachment = 'No se adjuntaron archivos';

        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug = 0; //SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = $smtpHost;                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = $emailUserName;                     //SMTP username
            $mail->Password   = $emailPassword;                               //SMTP password
            $mail->SMTPSecure = $smtpEncryption;            //Enable implicit TLS encryption
            $mail->Port       = $smtpPort;                                    //TCP port to connect to; use 587 if you have set SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS


            // Establecer el remitente y la dirección de envío real
            $mail->setFrom($emailUserName, $company);
            $mail->addAddress($emailUserName);

            // Establecer el remitente que se mostrará en el campo "From"
            $mail->clearReplyTos(); // Eliminar las direcciones de respuesta anteriores (si las hubiera)
            if($email){
                $mail->addReplyTo($email, $name);
            }

            $mail->CharSet = 'UTF-8';

            if ($file && $file->getSize() > 0) {
                $attachment = $file->getClientOriginalName();
                // Obtener el tipo MIME del archivo
                $mimeType = $file->getMimeType();


                // Definir los tipos MIME permitidos
                $allowedMimeTypes = [
                    // Imágenes
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/bmp',
                    'image/webp',
                    'image/svg+xml',
                    // Documentos de texto
                    // 'text/plain',
                    // 'application/msword', // .doc
                    // 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
                    // 'application/vnd.oasis.opendocument.text', // .odt
                    // PDF
                    'application/pdf',
                    // Hojas de cálculo
                     'application/vnd.ms-excel', // .xls
                     'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                    // 'application/vnd.oasis.opendocument.spreadsheet', // .ods
                    // Presentaciones
                    // 'application/vnd.ms-powerpoint', // .ppt
                    // 'application/vnd.openxmlformats-officedocument.presentationml.presentation', // .pptx
                    // 'application/vnd.oasis.opendocument.presentation', // .odp
                    // Archivos comprimidos
                    // 'application/zip',
                    // 'application/x-rar-compressed',
                    // 'application/x-tar',
                    // 'application/x-7z-compressed',
                    // 'application/x-gzip',
                    // Audio
                    // 'audio/mpeg',
                    // 'audio/ogg',
                    // 'audio/wav',
                    // 'audio/x-ms-wma',
                    // Video
                    // 'video/mp4',
                    // 'video/mpeg',
                    // 'video/quicktime',
                    // 'video/x-msvideo',
                    // 'video/x-flv',
                ];

                // Verificar si el tipo MIME es válido
                if (in_array($mimeType, $allowedMimeTypes)) {
                    //Attachments
                    $mail->addAttachment($file->getRealPath(), $attachment);         //Add attachments
                    //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name
                }
                else
                {
                    $attachment = 'El remitente intentó enviar un archivo no soportado:'."\n" .$mimeType ."\n" .'Nombre del archivo: '.$file->getClientOriginalName();
                }
            }

            // Formatear mensaje en HTML con los datos del remitente
            $body = "<ul style='font-size:16px;'>
                    <li><strong style='font-size:18px;'>Nombre:</strong> $name</li>";
            $body.= $phone ? "<li><strong style='font-size:18px;'>Teléfono:</strong> $phone</li>" : '';
            $body.= $email ? "<li><strong style='font-size:18px;'>E-mail:</strong> $email</li>" : '';
            $body.="</ul>
                    <br/><br/>
                    <h4>Mensaje:</h4>
                    <p>$message</p>";
            $body.= $attachment ? "<br/><br/><br/><h5>Archivos adjuntos</h5><p>$attachment</p>" : '';

            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = $title;
            $mail->Body    = $body;
            //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            $mailSent = $mail->send();

            if ($mailSent) {
                return redirect()->back()->with([
                    'message' => '¡El mensaje se envió correctamente!'
                ]);
            } else {
                return redirect()->back()->withErrors([
                    'message' => 'Error al enviar el mensaje',
                    'errors' => $mail->ErrorInfo
                ]);
            }
        } catch (Exception $e) {
            return redirect()->back()->withErrors([
                'message' => 'Error al enviar el mensaje',
                'errors' => $e->getMessage()
            ]);
        }
    }
}
