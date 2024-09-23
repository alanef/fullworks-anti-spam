<?php

namespace Fullworks_Anti_Spam;

use Fullworks_Anti_Spam\Core\Email_Log;
use Fullworks_Anti_Spam\Core\Forms_Registrations;
use Fullworks_Anti_Spam\Core\Spam_Checks;
use Fullworks_Anti_Spam\Core\Utilities;

class Anti_Spam_Api {


	/**
	 * @var Forms_Registrations $forms_registrations
	 */
	private $forms_registrations;
	public $spam_level;

	public function __construct() {
		add_filter( 'fwas_is_spam', array( $this, 'is_spam' ), 10, 4 );
		$this->forms_registrations = new Forms_Registrations();
		$this->forms_registrations->register_forms();

	}

	/**
	 * Retrieves the array of registered forms.
	 *
	 * @return array The array of registered forms.
	 * @api
	 */
	public function get_registered_forms() {
		return $this->forms_registrations->get_registered_forms();
	}

	/**
	 * Set the registered forms.
	 *
	 * @param array $forms The array of registered forms.
	 *
	 * @return void
	 * @api
	 */
	public function set_registered_forms( $forms ) {
		$this->forms_registrations->set_registered_forms( $forms );
	}


	/**
	 * Updates the registered form with the provided form system and form data.
	 *
	 * @param string $form_system The system of the form to be updated.
	 * @param array $form The updated form data.
	 *
	 * @return void
	 * @api
	 */
	public function update_registered_form( $form_system, $form ) {
		$this->forms_registrations->update_registered_form( $form_system, $form );
	}

	/**
	 * Check if an email is spam.
	 *
	 * @param boolean|string $spam The initial spam status (if already determined. False if not currently spam).
	 * @param string $form_system The form system.
	 * @param string $email The email address.
	 * @param string $message The email message content.
	 *
	 * @return boolean|string       False if not spam, or the spam status ('DENY', 'BOT', 'IP_BLK_LST', 'SNGL_WRD', 'HUMAN').
	 * @api
	 *
	 * @filter fwas_is_spam<br>
	 *   <em>example<em><br>
	 *  <pre>$is_spam = apply_filters(
	 *    'wfwas_is_spam',
	 *     false,          // always false unless you have another pre check
	 *     $form_system,   // your own key 'my_form_sys'
	 *     $email,         // the first email your form collects
	 *     $message,       //  combine the text areas of your form with a space between
	 *  );</pre>
	 *
	 * add_filter( 'fwas_is_spam', array( $this, 'is_spam' ), 10, 4 );
	 */
	public function is_spam( $spam, $form_system, $email, $message, $offline = false, $options=array() ) {
		$spam_checks = new Spam_Checks();

		$result = $spam_checks->is_spam( $spam, $form_system, $email, $message, $offline, $options );
		
		$this->spam_level = $spam_checks->get_spam_level();

		return $result;
	}

	/**
	 * Inserts an email log into the database.
	 * This function should be used when messages are not being sent via wp_mail
	 * and you want to log more than just the tested content in the message
	 *
	 * @param array $email_array An array containing arguments typically passed to wp_mail:
	 * - string or array $to: The intended recipient(s) of the mail. It can be a string of an email address or an array of email addresses.
	 * - string $subject: The subject of the email.
	 * - string $message: The body of the email.
	 * - string or array $headers (optional): The headers of the email. It can be a string of a single header,
	 *   or an array of multiple headers. Default is an empty string.
	 *
	 * @return void
	 * @api
	 */
	public function insert_email_log( $email_array, $form_system = '' ) {
		global $fwantispam_fs;
		$email_log = new Email_Log( Utilities::get_instance(), $fwantispam_fs );
		$email_log->insert_email_log( $email_array, $form_system );
	}
}