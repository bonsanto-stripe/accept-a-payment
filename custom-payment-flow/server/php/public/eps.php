<?php
require_once 'shared.php';

// Returning after redirecting to the Afterpay/Clearpay portal.
if(array_key_exists('return', $_GET) && $_GET['return'] == 'true') {
  $paymentIntent = \Stripe\PaymentIntent::retrieve([
    'id' => $_GET['payment_intent']
  ]);
  echo "<p>Payment " . $paymentIntent->id . " has status: " . $paymentIntent->status . '</p>';
  echo "<a href='/eps.php'>Try EPS again</a><br>";
  echo "<a href='/'>Restart demo</a>";
  exit;
}

try {
  $paymentIntent = \Stripe\PaymentIntent::create([
    'payment_method_types' => ['eps'],
    'amount' => 1999,
    'currency' => 'eur',
  ]);
} catch (\Stripe\Exception\ApiErrorException $e) {
  http_response_code(400);
  echo json_encode(['error' => ['message' => $e->getError()->message]]);
  exit;
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode($e);
  exit;
}
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>EPS</title>
    <link rel="stylesheet" href="css/base.css" />
    <script src="https://js.stripe.com/v3/"></script>
    <script>
      document.addEventListener('DOMContentLoaded', async () => {
        const stripe = Stripe('<?php echo $config["stripe_publishable_key"] ?>');

        const elements = stripe.elements();
        const epsBank = elements.create('epsBank');
        epsBank.mount('#eps-bank-element');

        const paymentForm = document.querySelector('#payment-form');
        paymentForm.addEventListener('submit', async (e) => {
          // Avoid a full page POST request.
          e.preventDefault();

          // Customer inputs
          const nameInput = document.querySelector('#name');

          // Confirm the payment that was created server side:
          const {error, paymentIntent} = await stripe.confirmEpsPayment(
            '<?php echo $paymentIntent->client_secret ?>', {
              payment_method: {
                eps: epsBank,
                billing_details: {
                  name: nameInput.value,
                },
              },
              return_url: `${window.location.origin}/eps.php?return=true`,
            },
          );
          if(error) {
            alert(error.message);
            return;
          }
          alert(`Payment (${paymentIntent.id}): ${paymentIntent.status}`);
        });
      });
    </script>
  </head>
  <body>
    <main>
      <a href="/">home</a>
      <h1>EPS</h1>

      <form id="payment-form">
        <label for="name">
          Name
        </label>
        <input id="name" value="Jenny Rosen" required />

        <label for="eps-bank-element">
          EPS Bank
        </label>
        <div id="eps-bank-element">
          <!-- A Stripe Element will be inserted here. -->
        </div>

        <button id="submit">Pay</button>
      </form>

      <div id="messages" role="alert" style="display: none;"></div>
    </main>
  </body>
</html>