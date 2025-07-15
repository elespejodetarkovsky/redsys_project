import axios from 'axios';

/**
 * declaraciones de variables
 */

let iniciarPost = '/card_information';

let cardnumber              = document.getElementById('card_number');
let cardExp                 = document.getElementById('card_exp');
let cardCvv                 = document.getElementById('card_cvv');
let amount                  = document.getElementById("amount");
let order                           = document.getElementById("order");
let iniciarButton           = document.getElementById("iniciar");
let threeDsmethodForm       = document.getElementById("3dsmethod-form");
let challengeForm           = document.getElementById("challenge-form");
let threeDsmethodIframe     = document.getElementById("3dsmethod-iframe");
let stateTransaction        = document.getElementById("state_trans");
let challengeIframe         = document.getElementById("redsys_iframe_challenge");

threeDsmethodIframe.addEventListener("load", function(e) {

    if ( !(threeDsmethodIframe.contentDocument === null) )
    {
        const challenge = JSON.parse( threeDsmethodIframe.contentDocument.body.innerText );

        if ( challenge.ChallengeRequest )
        {
            stateTransaction.value = "se necesita realizar challenge"

            executeChallenge( challenge );
            //se carga form y ejecuta en el iframe
        }

        //console.log(threeDsmethodIframe, JSON.parse( threeDsmethodIframe.contentDocument.body.innerText ));
    }

})

challengeIframe.addEventListener("load", function(e) {


    console.log( challengeIframe.contentDocument );

     if ( !(challengeIframe.contentDocument === null) )
     {
         const result = JSON.parse( challengeIframe.contentDocument.body.innerText );

         console.log( result.error );
         if ( !result.error )
         {
             stateTransaction.value = "se ha realizado el pago";
         } else
         {
             stateTransaction.value = "se ha producido un error ... colocar error";
         }

     }

})

iniciarButton.addEventListener("click", function(){
    let threeDSMethodURL = '';
    let threeDSMethodData = '';

    let transaction = {
        transOrder: orderNumber(),
        amount: "7878",
        card: {
            pan: cardnumber.value,
            expDate: cardExp.value,
            cvv: cardCvv.value,
        }
    };

    axios.post( iniciarPost,  transaction,
    ).then(function (response) {

        //devolverá un json con los parámetros para enviar en form y
        //se evaluará el riesgo de la operación (opcional pero recomendado)

        executeThreeDsMethod ( response.data );

    })
    .catch(function (error) {
        console.log(error);
    });

    //TODO evaluar el response si está todo bien se ejecuta colocarlas en false?
})

/**
 * ejecutará el challenge y lo enviará al iframe correspondiente
 * @param protocolVersion
 * @param acsURL
 * @param creq
 */
function executeChallenge( { protocolVersion, acsURL, creq } ) {

    challengeForm.action = acsURL;
    challengeForm.method = 'post';

    const creqData = document.createElement('input');

    creqData.setAttribute('type', 'hidden');
    creqData.setAttribute('name', 'creq');

    creqData.setAttribute('value', creq);

    challengeForm.appendChild(creqData);

    console.log( challengeForm );

    challengeForm.target = 'redsys_iframe_challenge';
    challengeIframe.style.display = 'block';

    stateTransaction.value = "debe realizar challenge"

    challengeForm.submit();

}

function executeThreeDsMethod( { threeDSMethodURL, threeDSMethodData } ) {


    //const f = document.createElement('form');
    //document.body.appendChild(f);
    threeDsmethodForm.action = threeDSMethodURL;
    threeDsmethodForm.method = 'post';

    const dsMethodData = document.createElement('input');

    dsMethodData.setAttribute('type', 'hidden');
    dsMethodData.setAttribute('name', 'threeDSMethodData');

    dsMethodData.value = threeDSMethodData;

    threeDsmethodForm.appendChild(dsMethodData);

    console.log(threeDsmethodForm, dsMethodData);
    //threeDsmethodIframe.style.display = 'block';

    threeDsmethodForm.target = "3dsmethod";
    stateTransaction.value = "tarjeta correcta evaluando riesgo operacion"

    threeDsmethodForm.submit();

}
window.addEventListener('load', function (){

    //order.value = orderNumber();
    //amount.value = "7878";

    console.log('carga pagina');

});

function orderNumber() {


    //genero un numero en base a date unix único
    //console.log('num_order: ');
    order = Math.floor(Date.now() / 1000);

    console.log('genero numero order', order);
    //como es tan estricto con el tema de los string hago la conversión
    return order.toString();
}