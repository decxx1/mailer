<?php

namespace App\Http\Controllers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FranchiseMailerController extends Controller
{
    public function index(Request $request):JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'phone' => 'required|string|max:50',
            'email' => 'required|email',
            'address' => 'required|string|max:50',
            'addressee' => 'required|email',
            'country' => 'required|string|max:50',
            'state' => 'required|string|max:50',
            'city' => 'required|string|max:50',
            'zip' => 'required|string|max:50',

            'consult1_1' => 'required|boolean',
            'sector' => 'nullable|string|max:50',
            'consult2_1' => 'required|boolean',

            'estetica' => 'nullable|boolean',
            'depilacion_laser' => 'nullable|boolean',
            'cuidado_personal' => 'nullable|boolean',
            'fotodepilacion' => 'nullable|boolean',
            'luz_pulsada' => 'nullable|boolean',


            'conocio' => 'required|string|max:150',
            'ubicar_centro' => 'required|string|max:150',
            'comunicar' => 'required|string|max:150',
            'consulta_extra' => 'nullable|string|max:500',

            'token' => 'required|string',
            'secret_key' => 'required|string',

        ]);


        if ($validator->stopOnFirstFailure()->fails()) {
            return response()->json([
                'message' => 'Error en el formulario',
                'errors' => $validator->errors()
            ], 400);
        }
        try {
            $validated = $validator->validated();

            $credentials = [
                'mail_username' => env('MAIL_USERNAME'),
                'mail_password' => env('MAIL_PASSWORD'),
                'mail_host' => env('MAIL_HOST'),
                'mail_port' => env('MAIL_PORT'),
                'mail_encryption' => env('MAIL_ENCRYPTION'),
                'mail_from_name' => env('MAIL_FROM_NAME')
            ];
            $title = 'Contacto por franquicia - de '.$validated['name'];
            $contact = [
                'name' => $validated['name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'address' => $validated['address'],
                'country' => $validated['country'],
                'state' => $validated['state'],
                'city' => $validated['city'],
                'zip' => $validated['zip'],
            ];
            $data = [
                'addressee' => $validated['addressee'],
                'title' => $title,
                'consult1_1' => $validated['consult1_1'],
                'sector' => $validated['sector'],
                'consult2_1' => $validated['consult2_1'],

                'estetica' => $validated['estetica'] ?? false,
                'depilacion_laser' => $validated['depilacion_laser'] ?? false,
                'cuidado_personal' => $validated['cuidado_personal'] ?? false,
                'fotodepilacion' => $validated['fotodepilacion'] ?? false,
                'luz_pulsada' => $validated['luz_pulsada'] ?? false,

                'conocio' => $validated['conocio'],
                'ubicar_centro' => $validated['ubicar_centro'],
                'comunicar' => $validated['comunicar'],
                'consulta_extra' => $validated['consulta_extra'],
            ];

            $endpoint = 'https://www.recaptcha.net/recaptcha/api/siteverify';

            $response = Http::asForm()->post($endpoint, [
                'secret' => $validated['secret_key'],
                'response' => $validated['token'],
            ])->json();

            if( $response['success'] && $response['score'] > 0.5) {
                $responseSend = $this->send($credentials, $data, $contact  );
            }else{
                return response()->json([
                    'message' => 'Error en el captcha',
                    'response' => $response
                ], 500);
            }

            if ($responseSend['status'] !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => $responseSend['message'],
                    'errors' => $responseSend['errors']
                ], 500);
            }

            // Si la subfunción fue exitosa, devuelve la respuesta de éxito
            return response()->json([
                'message' => $responseSend['message']
            ], 200);

        } catch (Exception $e) {
            return response()->json(['message' => 'Error interno del servidor'], 500);
        }

    }

    public function send($credentials, $data, $contact)
    {
        //datos de SMPT
        $emailUserName = $credentials['mail_username'];
        $emailPassword = $credentials['mail_password'];
        $smtpHost = $credentials['mail_host'];
        $smtpPort = $credentials['mail_port'];
        $smtpEncryption = $credentials['mail_encryption'];
        $mailfromname = $credentials['mail_from_name']; // nombre de la persona que envía el correo

        //data
        $addressee = $data['addressee'];
        $title = $data['title'];
        $consult1_1 = $data['consult1_1'];
        $sector = $data['sector'];
        $consult2_1 = $data['consult2_1'];

        $estetica = $data['estetica'];
        $depilacion_laser = $data['depilacion_laser'];
        $cuidado_personal = $data['cuidado_personal'];
        $fotodepilacion = $data['fotodepilacion'];
        $luz_pulsada = $data['luz_pulsada'];

        $conocio = $data['conocio'];
        $ubicar_centro = $data['ubicar_centro'];
        $comunicar = $data['comunicar'];
        $consulta_extra = $data['consulta_extra'];
        //datos de contacto
        $name = $contact['name'];
        $last_name = $contact['last_name'];
        $phone = $contact['phone'];
        $email = $contact['email'];
        $address = $contact['address'];
        $country = $contact['country'];
        $state = $contact['state'];
        $city = $contact['city'];
        $zip = $contact['zip'];

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

            // Formatear mensaje en HTML con los datos del remitente
            $body = "<html><body>
            <h1>Mensaje fue enviado por: " . $name . ' ' . $last_name .  "</h1>
            <h3>Datos del contacto:</h3>";
            $body .= "<p><strong>E-mail.:</strong> " . $email. "</p>";
            $body .= "<p><strong>Tel:</strong> " . $phone. "</p>";
            $body .= "<p><strong>Dirección:</strong> " . $address. "</p>";
            $body .= "<p><strong>Ciudad:</strong> " . $city. "</p>";
            $body .= "<p><strong>Estado/Provincia:</strong> " . $state. "</p>";
            $body .= "<p><strong>Código postal:</strong> " . $zip. "</p>";
            $body .= "<p><strong>País:</strong> " . $country. "</p>";
            $body .= "<br/><h3>Encuesta</h3><br/>";
            $body .= "<p><strong>¿Ha trabajado en una franquicia alguna vez?:</strong> " . $consult1_1. "</p>";
            $body .= "<p><strong>¿en qué sector?:</strong> " . $sector. "</p>";
            $body .= "<p><strong>¿Tiene conocimientos en Depilación Láser o Estética?</strong> " . $consult2_1. "</p>";
            $body .= "<br/><h3>Experiencia con:</h3>";
            $body .= "<p><strong>Estética:</strong> " . $estetica. "</p>";
            $body .= "<p><strong>Depilación Láser:</strong> " . $depilacion_laser. "</p>";
            $body .= "<p><strong>Cuidado personal:</strong> " . $cuidado_personal. "</p>";
            $body .= "<p><strong>Fotodepilacion:</strong> " . $fotodepilacion. "</p>";
            $body .= "<p><strong>Luz pulsada:</strong> " . $luz_pulsada. "</p>";
            $body .= "<br/><h3>¿Cómo nos conoció?</h3>";
            $body .= "<p>" . $conocio. "</p>";
            $body .= "<br/><h3>¿En qué zona geográfica desea ubicar su centro?</h3>";
            $body .= "<p>" . $ubicar_centro. "</p>";
            $body .= "<br/><h3>¿En qué día y horario nos podemos comunicar con usted?</h3>";
            $body .= "<p>" . $comunicar. "</p>";
            $body .= "<br/><h3>Consulta</h3>";
            $body .= "<p>" . nl2br($consulta_extra) . "</p>
            </body></html>";

            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = $title;
            $mail->Body    = $body;

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
