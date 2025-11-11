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
     * Track if forms have been initialized
     *
     * @var bool
     */
    private static $forms_initialized = false;

    /**
     * The Freemius object used for handling plugin licensing and updates.
     *
     * @var \Freemius $freemius object
     */
    private $freemius;

    public function __construct() {
        /** @var \Freemius $fwantispam_fs Freemius global object. */
        global $fwas_fs;
        $this->freemius = $fwas_fs;
    }

    /**
     * Retrieves the array of registered forms.
     * Lazy initialization - registers forms on first access (after init hook)
     *
     * @return array The array of registered forms.
     */
    public static function get_registered_forms() {
        if ( !self::$forms_initialized ) {
            // If init hasn't fired yet, return empty array to avoid early translation loading
            if ( !did_action( 'init' ) ) {
                return array();
            }
            $instance = new self();
            $instance->register_forms();
            self::$forms_initialized = true;
        }
        return self::$registered_forms;
    }

    /**
     * Get form keys (slugs) that have a specific protection level.
     *
     * @param int $protection_level The protection level to filter by (1, 2, or 3).
     * @return array Array of form keys (e.g., ['cf7', 'wpforms', 'fluent']).
     */
    public static function get_form_keys_by_protection_level( $protection_level ) {
        $forms = self::get_registered_forms();
        $filtered = array();
        foreach ( $forms as $key => $form ) {
            if ( isset( $form['protection_level'] ) && $form['protection_level'] === $protection_level ) {
                $filtered[] = $key;
            }
        }
        return $filtered;
    }

    /**
     * Get installed form names that have a specific protection level.
     *
     * @param int $protection_level The protection level to filter by (1, 2, or 3).
     * @return array Array of installed form names (e.g., ['Contact Form 7', 'WPForms']).
     */
    public static function get_installed_form_names_by_protection_level( $protection_level ) {
        $forms = self::get_registered_forms();
        $installed_names = array();
        foreach ( $forms as $key => $form ) {
            if ( isset( $form['protection_level'] ) && $form['protection_level'] === $protection_level ) {
                // Form is registered, which means it's installed (registration checks installation)
                if ( isset( $form['name'] ) ) {
                    $installed_names[] = $form['name'];
                }
            }
        }
        return $installed_names;
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
        if ( Utilities::get_instance()->is_contact_form_7_installed() ) {
            $this->update_registered_form( 'cf7', array(
                'name'              => esc_html__( 'Contact Form 7', 'fullworks-anti-spam' ),
                'selectors'         => '.wpcf7-form',
                'protection_level'  => 1,
                'email_log'         => false,
                'email_mail_header' => 'X-WPCF7-Content-Type',
            ) );
        }
        if ( Utilities::get_instance()->is_wp_forms_lite_installed() ) {
            $this->update_registered_form( 'wpforms', array(
                'name'                     => esc_html__( 'WPforms', 'fullworks-anti-spam' ),
                'selectors'                => '.wpforms-form',
                'protection_level'         => 1,
                'email_log'                => false,
                'email_mail_header'        => 'X-WPFLite-Sender',
                'email_mail_header_filter' => 'wpforms_emails_mailer_get_headers',
                'unstoppable'              => true,
            ) );
        }
        if ( Utilities::get_instance()->is_jetpack_contact_form_installed() ) {
            $this->update_registered_form( 'grunion', array(
                'name'             => esc_html__( 'JetPack Contact Form', 'fullworks-anti-spam' ),
                'selectors'        => 'form.contact-form .grunion-field-wrap',
                'protection_level' => 1,
                'email_log'        => false,
                'spam_admin_url'   => 'edit.php?post_status=spam&post_type=feedback&s=%s',
            ) );
        }
        if ( Utilities::get_instance()->is_fluent_forms_installed() ) {
            $this->update_registered_form( 'fluent', array(
                'name'                     => esc_html__( 'Fluent Forms', 'fullworks-anti-spam' ),
                'selectors'                => '.frm-fluent-form',
                'protection_level'         => 1,
                'email_log'                => false,
                'email_mail_header'        => 'X-Fluent-Sender',
                'email_mail_header_filter' => 'fluentform/email_template_header',
                'unstoppable'              => true,
            ) );
        }
        if ( Utilities::get_instance()->is_sureforms_installed() ) {
            $this->update_registered_form( 'sureforms', array(
                'name'             => esc_html__( 'SureForms', 'fullworks-anti-spam' ),
                'selectors'        => '.srfm-form',
                'protection_level' => 1,
                'email_log'        => false,
            ) );
        }
        // Register premium-only forms with protection_level = 0 (no protection in free version)
        if ( Utilities::get_instance()->is_gravity_forms_installed() ) {
            $this->update_registered_form( 'gravity', array(
                'name'             => esc_html__( 'Gravity Forms', 'fullworks-anti-spam' ),
                'protection_level' => 0,
            ) );
        }
        if ( Utilities::get_instance()->is_quick_contact_forms_installed() ) {
            $this->update_registered_form( 'qcf', array(
                'name'             => esc_html__( 'Quick Contact Form', 'fullworks-anti-spam' ),
                'protection_level' => 0,
            ) );
        }
        $this->set_registered_forms( apply_filters( 'fwas_registered_forms', self::$registered_forms ) );
    }

}
