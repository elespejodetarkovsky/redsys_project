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
let threeDsmethodIframe     = document.getElementById("3dsmethod-iframe");


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
    threeDsmethodForm.target = "3dsmethod";
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