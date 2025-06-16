<?php

namespace App\Controller;

use App\Service\FetchService;
use App\Service\RedsysService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RedsysController extends AbstractController
{

    public function __construct( private RedsysService $redsysService, private FetchService $fetchService )
    {
    }

    /**
     * @param string $order
     * @param string $amount
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    #[Route('/iniciarPeticion/{order}/{amount}/{pan}/{expdate}/{cvv2}', name: 'app_redsys_init')]
    public function initPeticion( string $order, string $amount, string $pan, string $expdate, string $cvv2 ): JsonResponse
    {

        //TODO esto hay que modificarlo agregar la carga de parámetros en un servicio?
        $this->amount               = $amount;
        $this->order                = $order;


        // Se Rellenan los campos
        $this->redsysService->setParameter("DS_MERCHANT_ORDER",$order);
        $this->redsysService->setParameter("DS_MERCHANT_MERCHANTCODE", $this->getParameter('app.fuc') );
        $this->redsysService->setParameter("DS_MERCHANT_TERMINAL",$this->getParameter('app.terminal'));
        $this->redsysService->setParameter("DS_MERCHANT_TRANSACTIONTYPE",RedsysService::AUTHORIZATION);
        $this->redsysService->setParameter("DS_MERCHANT_PAN", $pan);
        $this->redsysService->setParameter("DS_MERCHANT_EXPIRYDATE", $expdate);
        $this->redsysService->setParameter("DS_MERCHANT_CVV2", $cvv2);
        $this->redsysService->setParameter("DS_MERCHANT_CURRENCY", $this->getParameter('app.currency') );
        $this->redsysService->setParameter("DS_MERCHANT_AMOUNT",$amount);
        $this->redsysService->setParameter("DS_MERCHANT_EMV3DS",'{"threeDSInfo": "CardData"}'); //se solicita información de la tarjeta enviada
        $this->redsysService->setParameter("DS_MERCHANT_EXCEP_SCA", "Y"); //envío de excensiones de la tarjeta por si la necesitamos

        $dsSignatureVersion     = 'HMAC_SHA256_V1';

        //diversificación de clave 3DES
        //OPENSSL_RAW_DATA=1

        $params = $this->redsysService->createMerchantParameters();
        $signature = $this->redsysService->createMerchantSignature($this->getParameter('app.clave.comercio'));

        $petition['Ds_SignatureVersion']        = $dsSignatureVersion;
        $petition["Ds_MerchantParameters"]      = $params;
        $petition["Ds_Signature"]               = $signature;


        return $this->json($this->fetchService->fetch( json_encode($petition), true), Response::HTTP_OK);

    }
}