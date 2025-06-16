<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Emv3DS;
use App\Entity\Transaction;
use App\Form\ThreeDsMethodType;
use App\Service\FetchService;
use App\Service\RedsysService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Exception\AlreadySubmittedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;


class CardInformationController extends AbstractController
{

    public function __construct( private RedsysService $redsysService, private FetchService $fetchService, private EntityManagerInterface $entityManager )
    {
    }

    #[Route('/card_information', name: 'card_information', methods: ['POST'])]
    public function index( #[MapRequestPayload] Transaction $transaction )
    {

        $this->redsysService->setParameter("DS_MERCHANT_ORDER", $transaction->getTransOrder());
        $this->redsysService->setParameter("DS_MERCHANT_MERCHANTCODE", $this->getParameter('app')['fuc'] );
        $this->redsysService->setParameter("DS_MERCHANT_TERMINAL", $this->getParameter('app')['terminal'] );
        $this->redsysService->setParameter("DS_MERCHANT_TRANSACTIONTYPE",RedsysService::AUTHORIZATION);
        $this->redsysService->setParameter("DS_MERCHANT_PAN", $transaction->getCard()->getPan() );
        $this->redsysService->setParameter("DS_MERCHANT_EXPIRYDATE", $transaction->getCard()->getExpDate() );
        $this->redsysService->setParameter("DS_MERCHANT_CVV2", $transaction->getCard()->getCvv()  );
        $this->redsysService->setParameter("DS_MERCHANT_CURRENCY", $this->getParameter('app')['currency'] );
        $this->redsysService->setParameter("DS_MERCHANT_AMOUNT", $transaction->getAmount() );
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


        if ( array_key_exists( 'errorCode', $respuesta ) )
        {
            //TODO buscar el error en la base de datos
            throw new HttpException(500, 'error: '.$respuesta['errorCode']);
        }

        $parameters = json_decode( $this->redsysService->decodeMerchantParameters( $respuesta['Ds_MerchantParameters'] ), true);

        $transaction->setTransType('0')
            ->setCurrency( $this->getParameter('app')['currency']  );

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

            //hago la consulta con el formulario
//            $form = $this->createForm( ThreeDsMethodType::class, ['action' => $parameters['Ds_EMV3DS']['threeDSMethodURL']]);
//
//            try {
//
//                $form->submit(['threeDSMethodData' => $threeDS->getThreeDSMethodData( $this->getParameter('app')['notificacion']['ds'].$transaction->getTransOrder() )]);
//
//            } catch ( \Exception $e )
//            {
//                return $this->json(['threeDSMethodSubmit' => false]);
//            }
//
//            return $this->json(['threeDSMethodSubmit' => true]);

            return $this->json([
                'threeDSMethodURL'      => $parameters['Ds_EMV3DS']['threeDSMethodURL'],
                'threeDSServerTransID'  => $parameters['Ds_EMV3DS']['threeDSServerTransID'],
                'threeDSMethodData'     => $threeDS->getThreeDSMethodData( $this->getParameter('app')['notificacion']['ds'].$transaction->getTransOrder() )
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

}