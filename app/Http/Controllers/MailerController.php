<?php

namespace App\Http\Controllers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use App\Http\Requests\SendMailRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailerController extends Controller
{
    public function test()
    {
        return response()->json([
            'message' => 'ok',
        ], 200);
    }
    public function index(SendMailRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $credentials = [
                'mail_username' => env('MAIL_USERNAME'),
                'mail_password' => env('MAIL_PASSWORD'),
                'mail_host' => env('MAIL_HOST'),
                'mail_port' => env('MAIL_PORT'),
                'mail_encryption' => env('MAIL_ENCRYPTION'),
                'mail_from_name' => env('MAIL_FROM_NAME')
            ];
            $title = $validated['asunto'];
            $data = [
                'addressee' => $validated['addressee'],
                'name' => $validated['name'],
                'message' => $validated['message'],
                'title' => $title
            ];
            $email = $validated['email'] ?? null;
            $phone = $validated['phone'] ?? null;
            $file =  $request->hasFile('file') ? $request->file('file') : null;

            $endpoint = 'https://www.recaptcha.net/recaptcha/api/siteverify';

            $response = Http::asForm()->post($endpoint, [
                'secret' => $validated['secret_key'],
                'response' => $validated['token'],
            ])->json();

            if( $response['success'] && $response['score'] > 0.5) {
                $responseSend = $this->send($credentials, $data, $email, $phone, $file );
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Error en el captcha',
                    'response' => $response
                ], 500);
            }

            if ($responseSend['status'] !== 200) {
                return response()->json([
                    'success' => false,
                    'message' => $responseSend['message'],
                    'errors' => $responseSend['errors']
                ], 500);
            }

            // Si la subfunción fue exitosa, devuelve la respuesta de éxito
            return response()->json([
                'success' => true,
                'message' => $responseSend['message']
            ], 200);

        } catch (Exception $e) {
            return response()->json(['message' => 'Error interno del servidor'], 500);
        }

    }

    public function send($credentials, $data, $email = null, $phone = null, ?UploadedFile $file = null)
    {
        //datos de SMPT
        $emailUserName = $credentials['mail_username'];
        $emailPassword = $credentials['mail_password'];
        $smtpHost = $credentials['mail_host'];
        $smtpPort = $credentials['mail_port'];
        $smtpEncryption = $credentials['mail_encryption'];
        $mailfromname = $credentials['mail_from_name']; // nombre de la persona que envía el correo

        //datos de envío
        $addressee = $data['addressee'];
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
            $mail->setFrom($emailUserName, $mailfromname);
            // destinatario
            $mail->addAddress($addressee);

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
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/bmp',
                    'image/webp',
                    'image/svg+xml',
                    'application/pdf',
                    'application/vnd.ms-excel', // .xls
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
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

            if ($mail->send()) {
                return [
                    'status' => 200,
                    'message' => '¡El mensaje se envió correctamente!'
                ];
            } else {
                return [
                    'status' => 500,
                    'message' => 'Error al enviar el mensaje',
                    'errors' => $mail->ErrorInfo
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 500,
                'message' => 'Error al enviar el mensaje',
                'errors' => $e->getMessage()
            ];
        }
    }
}
