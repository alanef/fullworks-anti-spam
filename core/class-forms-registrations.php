<?php

/**
 * Class Forms_Registrations
 *
 * Handles the registration and update of forms.
 */
namespace Fullworks_Anti_Spam\Core;

class Forms_Registrations {
    /**
     * The array holding registered form instances.
     *
     * @var array
     */
    private static $registered_forms;

    /**
     * The Freemius object used for handling plugin licensing and updates.
     *
     * @var \Freemius $freemius object
     */
    private $freemius;

    public function __construct() {
        /** @var \Freemius $fwantispam_fs Freemius global object. */
        global $fwantispam_fs;
        $this->freemius = $fwantispam_fs;
    }

    /**
     * Retrieves the array of registered forms.
     *
     * @return array The array of registered forms.
     */
    public static function get_registered_forms() {
        return self::$registered_forms;
    }

    /**
     * Set the registered forms.
     *
     * @param array $forms The array of registered forms.
     *
     * @return void
     */
    public function set_registered_forms( $forms ) {
        self::$registered_forms = $forms;
    }

    /**
     * Update the registered form.
     *
     * This method updates the registered form by either adding a new form or updating an existing form.
     *
     * @param array $form The form data to be registered or updated. If the 'form_system' key is present in the array,
     *                    it will update the form data with that form ID. Otherwise, it will add the form data as a new form.
     *
     * @return void
     */
    public function update_registered_form( $form_system, $form ) {
        self::$registered_forms[$form_system] = $form;
    }

    /**
     * Registers the forms for anti-spam protection.
     *
     * This method updates the array of registered forms with the specified form details. If the plugin supports premium features, additional form registrations will be added.
     *
     * @return void
     */
    public function register_forms() {
        // always register comments
        $this->update_registered_form( 'comments', array(
            'name'             => esc_html__( 'Comments', 'fullworks-anti-spam' ),
            'selectors'        => '#commentform, #comments-form,.comment-form, .wpd_comm_form',
            'protection_level' => 3,
            'email_log'        => false,
            'spam_admin_url'   => 'edit-comments.php?comment_status=spam',
            'spam_purge_cb'    => array('\\Fullworks_Anti_Spam\\Core\\Purge', 'purge_comment_spam'),
            'spam_count_cb'    => array('\\Fullworks_Anti_Spam\\Core\\Email_Reports', 'get_comments_count'),
        ) );
        $this->set_registered_forms( apply_filters( 'fwas_registered_forms', $this->get_registered_forms() ) );
    }

}
