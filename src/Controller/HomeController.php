<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Emv3DS;
use App\Entity\Transaction;
use App\Form\CardType;
use App\Form\TransactionType;
use App\Repository\TransactionRepository;
use App\Service\FetchService;
use App\Service\RedsysService;
use App\Util\Util;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{

    public function __construct( private RedsysService $redsysService, private FetchService $fetchService, private EntityManagerInterface $entityManager )
    {
    }

    #[Route('/', name: 'app_base', methods: ['GET'])]
    public function index()
    {
        return $this->redirectToRoute('app_home');
    }

    #[Route('/home', name: 'app_home')]
    public function home( Request $request )
    {

        $form = $this->createForm(CardType::class );

        $form->handleRequest( $request );

        if ( $form->isSubmitted() && $form->isValid() ) {

            $card = $form->getData(); //Card::class

            //TODO esto hay que modificarlo agregar la carga de parámetros en un servicio?
            if ( $card instanceof Card )
            {

                $amount     = '7878';//$card->getAmount();
                $order      = substr( str_shuffle( str_repeat( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 10)), 0, 12);//strval(strtotime('now'));//$card->getOrder();
                $pan        = $card->getPan();
                $expdate    = $card->getExpDate();
                $cvv2       = $card->getCvv();


            } else
            {
                //return $this->redirectToRoute('app_home');
            }


            // Se Rellenan los campos
            $this->redsysService->setParameter("DS_MERCHANT_ORDER",$order);
            $this->redsysService->setParameter("DS_MERCHANT_MERCHANTCODE", $this->getParameter('app')['fuc'] );
            $this->redsysService->setParameter("DS_MERCHANT_TERMINAL", $this->getParameter('app')['terminal'] );
            $this->redsysService->setParameter("DS_MERCHANT_TRANSACTIONTYPE",RedsysService::AUTHORIZATION);
            $this->redsysService->setParameter("DS_MERCHANT_PAN", $pan);
            $this->redsysService->setParameter("DS_MERCHANT_EXPIRYDATE", $expdate);
            $this->redsysService->setParameter("DS_MERCHANT_CVV2", $cvv2);
            $this->redsysService->setParameter("DS_MERCHANT_CURRENCY", $this->getParameter('app')['currency'] );
            $this->redsysService->setParameter("DS_MERCHANT_AMOUNT",$amount);
            $this->redsysService->setParameter("DS_MERCHANT_EMV3DS",'{"threeDSInfo": "CardData"}'); //se solicita información de la tarjeta enviada
            $this->redsysService->setParameter("DS_MERCHANT_EXCEP_SCA", "Y"); //envío de excensiones de la tarjeta por si la necesitamos

            $dsSignatureVersion     = 'HMAC_SHA256_V1';

            //diversificación de clave 3DES
            //OPENSSL_RAW_DATA=1

            $params = $this->redsysService->createMerchantParameters();

            $signature = $this->redsysService->createMerchantSignature( $this->getParameter('app')['clave']['comercio'] );

            $petition['Ds_SignatureVersion']        = $dsSignatureVersion;
            $petition["Ds_MerchantParameters"]      = $params;
            $petition["Ds_Signature"]               = $signature;


            $respuesta = json_decode( $this->fetchService->fetch( json_encode($petition), true) , true );
            //$respuesta = $this->json( $this->fetchService->fetch( json_encode($petition), true), Response::HTTP_OK );


            if ( array_key_exists( 'errorCode', $respuesta ) )
            {
                //TODO buscar el error en la base de datos
                throw new HttpException(500, 'error: '.$respuesta['errorCode']);
            }

            $parameters = json_decode( $this->redsysService->decodeMerchantParameters( $respuesta['Ds_MerchantParameters'] ), true);

            $transaction = new Transaction();

            $transaction->setTransOrder( $order )
                ->setTransType('0')
                ->setCurrency( $this->getParameter('app')['currency']  )
                ->setAmount( $amount )
                ->setCard( $card );

            $threeDS = new Emv3DS();
            $threeDS->setThreeDSInfo( $parameters['Ds_EMV3DS']['threeDSInfo'] )
                ->setProtocolVersion( $parameters['Ds_EMV3DS']['protocolVersion'] )
                ->setThreeDServerTransID( $parameters['Ds_EMV3DS']['threeDSServerTransID'] );

            if ( array_key_exists( 'threeDSMethodURL', $parameters['Ds_EMV3DS']) ) //se ejecuta 3DSMethod
            {

                    //construyo el json en función de dsserverID y notificationURL

                    //creo el objeto 3DS
                    //TODO serializarlo?
                    $threeDS->setThreeDSMethodURL( $parameters['Ds_EMV3DS']['threeDSMethodURL'] )
                        ->setCreatedAt( new \DateTimeImmutable() );

                    $transaction->setEmv3DS( $threeDS )
                        ->setIsPaid( false );

                    //dd($transaction);
                    //creo la transacción en la base de datos
                    $this->entityManager->persist( $transaction );

                    $this->entityManager->flush();

                    //hago el envío al template con el form
                    return $this->render('home/index.html.twig', [

                        'form' => $form->createView(),
                        'threeDSMethodURL'      => $parameters['Ds_EMV3DS']['threeDSMethodURL'],
                        'threeDSServerTransID'  => $parameters['Ds_EMV3DS']['threeDSServerTransID'],
                        'threeDSMethodData'     => $threeDS->getThreeDSMethodData( $this->getParameter('app')['notificacion']['ds'].$order )

                    ]);


            } else {

                $threeDS->setCreatedAt( new \DateTimeImmutable() );

                $transaction->setEmv3DS( $threeDS )
                    ->setIsPaid( false );

                //dd($transaction);
                //creo la transacción en la base de datos
                $this->entityManager->persist( $transaction );

                $this->entityManager->flush();

                //continuo con la operación y coloco el theeDSCompInd con el valor «N»
                $this->firstTrataPeticionMerchantParameters( $transaction, 'N' );

                $dsSignatureVersion     = 'HMAC_SHA256_V1';

                //diversificación de clave 3DES
                //OPENSSL_RAW_DATA=1

                $params = $this->redsysService->createMerchantParameters();

                $signature = $this->redsysService->createMerchantSignature( $this->getParameter('app')['clave']['comercio'] );

                $petition['Ds_SignatureVersion']        = $dsSignatureVersion;
                $petition["Ds_MerchantParameters"]      = $params;
                $petition["Ds_Signature"]               = $signature;

                $respuesta = json_decode( $this->fetchService->fetch( json_encode($petition)), true );

                return $this->render('home/index.html.twig', [
                    'form' => $form->createView(),
                ]);

            }

        }

        return $this->render('home/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/threeDsMethodNotification/{order}', name: 'app_3ds_notification', methods: ['POST'])]
    public function trheeDsMethodResponse( TransactionRepository $transactionRepository, string $order, Request $request )
    {

        //recupero el objeto para validarlo y continuar la transacción

        //$transaction = $transactionRepository->findByOrderIdAndNoPaid( $order, $threeDSServerTransID );
        $transaction = $transactionRepository->findBy(['transOrder' => $order])[0];

        $this->redsysService->cleanParameters();

        /* ha sido exitosa la busqueda */
        if ( $transaction instanceof Transaction )
        {

            //verifico que esté dentro de los 10 segundos
            if ( Util::secondsFromDateToNow( $transaction->getEmv3DS()->getCreatedAt() ) <= 10 )
            {
                /* dentro de lo esperable threeDSCompInd = Y */
                $this->firstTrataPeticionMerchantParameters( $transaction, "Y" );

            } else
            {
                /* el tiempo se ha excedido por tanto el parámetro a enviar es threeDSCompInd = N */
                $this->firstTrataPeticionMerchantParameters( $transaction, "N" );
            }

        } else
        {

            //TODO volver al home con el mensaje de error
            throw new HttpException(500, 'no existe la operacion solicitada');
        }


        $dsSignatureVersion     = 'HMAC_SHA256_V1';

        //diversificación de clave 3DES
        //OPENSSL_RAW_DATA=1

        $params = $this->redsysService->createMerchantParameters();

        $signature = $this->redsysService->createMerchantSignature( $this->getParameter('app')['clave']['comercio'] );

        $petition['Ds_SignatureVersion']        = $dsSignatureVersion;
        $petition["Ds_MerchantParameters"]      = $params;
        $petition["Ds_Signature"]               = $signature;

        //dd('trata_peticion_3ds', $params);

        $respuesta = json_decode( $this->fetchService->fetchTest( json_encode($petition)), true );

        $parameters = json_decode( $this->redsysService->decodeMerchantParameters( $respuesta['Ds_MerchantParameters'] ), true);

        //en este punto puede derivar en la solicitud del challenge "ChallengeRequest" o frictionless (parámetro Ds_Response: 0000)

        if ( $parameters["Ds_EMV3DS"]["threeDSInfo"] == "ChallengeRequest" )
        {

            return $this->json([
                "ChallengeRequest"  => true,
                "protocolVersion"   => $parameters["Ds_EMV3DS"]["protocolVersion"],
                "acsURL"            => $parameters["Ds_EMV3DS"]["acsURL"],
                "creq"              => $parameters["Ds_EMV3DS"]["creq"],
            ]);

        }

        return $this->redirectToRoute('app_home');
    }


    private function firstTrataPeticionMerchantParameters( Transaction $transaction, string $threeDSCompInd )
    {

        $emv3DS = json_encode( [ "threeDSInfo" => "AuthenticationData",
                                    "protocolVersion" => $transaction->getEmv3DS()->getProtocolVersion(),
                                    "browserJavascriptEnabled" => "false",
                                    "browserAcceptHeader" => "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8,application/json",
                                    "browserUserAgent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36",
                                    "browserJavaEnabled" => "false",
                                    "browserLanguage" => "ES-es",
                                    "browserColorDepth" => "24",
                                    "browserScreenHeight" => "1250",
                                    "browserScreenWidth" => "1320",
                                    "browserTZ" => "52",
                                    "threeDSServerTransID" => $transaction->getEmv3DS()->getThreeDServerTransID(),
                                    "notificationURL" => $this->getParameter('app')['notificacion']['url_first_trata'],
                                    "threeDSCompInd" => $threeDSCompInd,
        ], JSON_UNESCAPED_SLASHES );

        //$this->redsysService->cleanParameters();
        $this->redsysService->setParameter("DS_MERCHANT_ORDER",$transaction->getTransOrder() );
        $this->redsysService->setParameter("DS_MERCHANT_MERCHANTCODE", $this->getParameter('app')['fuc'] );
        $this->redsysService->setParameter("DS_MERCHANT_TERMINAL",$this->getParameter('app')['terminal'] );
        $this->redsysService->setParameter("DS_MERCHANT_TRANSACTIONTYPE",RedsysService::AUTHORIZATION );
        $this->redsysService->setParameter("DS_MERCHANT_CURRENCY", $this->getParameter('app')['currency'] );
        $this->redsysService->setParameter("DS_MERCHANT_PAN", $transaction->getCard()->getPan() );
        $this->redsysService->setParameter("DS_MERCHANT_EXPIRYDATE", $transaction->getCard()->getExpDate() );
        $this->redsysService->setParameter("DS_MERCHANT_AMOUNT",$transaction->getAmount() );
        $this->redsysService->setParameter("DS_MERCHANT_CVV2", $transaction->getCard()->getCvv() );
        $this->redsysService->setParameter("DS_MERCHANT_EMV3DS", $emv3DS ); //se solicita información de la tarjeta enviada

    }

    #[Route('/primer_trata_peticion/', name: 'primer_trata', methods: ['POST'])]
    public function firstTrataPeticion( Request $request )
    {
        dd("primer trata", $request->getPayload()->get('cres'));
    }

}