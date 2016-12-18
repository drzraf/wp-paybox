<?php

interface Payboxable {

  /* Going to paybox with an existing temporary token or only the persistent/permanent entity */

  /* This plugin allows two ordering model:
     A) The *BEFORE REDIRECT* persistance model relies on storing the final entity *before* the
     Paybox redirection.
     Pro: it is simpler
     Pro: the Paybox *uniq ID* is the number of the final entity
     Con: if Paybox payment fails (ex: 3D-secure or any other failures implying redirection),
          then it can not be retried (at least not using the same entity uniq number)     

     B) The *AFTER REDIRECT* persistance model allow for the final entity (ex: a given post-id) to be
     created only after a successful payment.
     Pro: this allows multiple attempts (ex 3D-secure may fail but implementer can retry later)
          In the case of a shop think "cart" versus "order"
     Con: this relies upon a "transitory" entity
     Con: the Paybox *uniq ID* is the number of this "transitory" (permanent entity is not created yet)
     

     In order to implement the "before redirect" persistence model (A):
     * Implementer's getPersistanceMode() must return MODE_PERSIST_BEFORE_REDIRECT
     * Implementer's must define set()

     In order to implement the "after redirect" persistence model (B):
     * Implementer's getPersistanceMode() must return MODE_PERSIST_AFTER_REDIRECT
     * Implementer's must define exists(), create() and update().


     

    Either:
    1) A previous attempt worked
    - we want to pay twice (re-POST)
    - we tweaked the cookie / want to access someone else data (hacky)
    2) previous attempt failed
    - we want to try again the payment using paybox (main use-case)
    We may at least exit; if the token was already paid.
    There is a case n°3) the payment succeeded, but we didn't
    received yet the hit at the IPN (still marked unpaid).
    In such case too we wouldn't allow a user the redo it's payment.

    Side note: Since we reuse order's values as PBX_CMD it's important to
    know how paybox deals with PBX_CMD reuse.
    Paybox documentation, p.40:
    > Ce champ doit être unique à chaque appel.
    Il est possible (mais pas certain), que paybox nous bloque lors de la seconde tentative,
    même si la première à pourtant échoué.
    Or PBX_CMD est notre seul moyen de retrouver une commande lors de l'IPN.
    Si l'hypothèse est vérifiée, un incrément ou autre sel devra être ajouté à notre
    clef unique déjà utilisée et l'IPN adaptée pour traiter ce genre de cas.

    > With multishipping, order reference are the same for all orders made with the same cart
    > in this case this method suffix the order reference by a # and the order number
  */
  const MODE_PERSIST_BEFORE_REDIRECT = 1;
  const MODE_PERSIST_AFTER_REDIRECT  = 2;


	function handleIPN($logguer);

  function getUniqId();
  function getEmail();
  function getAmount();
  function getCurrency();
  function isPayment3X();

  function onClientSuccess();
  function onClientError();
	function onClientConfirmation();
}


interface PrePersistPayboxable extends Payboxable {
  const PERSIST_MODE = self::MODE_PERSIST_BEFORE_REDIRECT;
  function set();
}


interface PostPersistPayboxable extends Payboxable {
  const PERSIST_MODE = self::MODE_PERSIST_AFTER_REDIRECT;
  function exists();
  function create();
  function update();
}
