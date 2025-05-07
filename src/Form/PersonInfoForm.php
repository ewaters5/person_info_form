<?php

namespace Drupal\person_info_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\EmailValidatorInterface;


class PersonInfoForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'person_info_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['logo'] = [
        '#type' => 'markup',
        '#markup' => '<div class="person-form-logo"><img src="/modules/custom/person_info_form/images/hmp-global-40-logo.svg" alt="Logo" /></div>',
        '#weight' => -100, // ensures it appears at the very top
      ];
    $form['form_header'] = [
        '#type' => 'markup',
        '#markup' => '
          <div class="form-header">
            <h1>Sign up</h1>
            <p class="form-subtitle">Enter your credentials</p>
          </div>
        ',
      ];
    $form['#prefix'] = '<div class="person-info-form-wrapper">';
    $form['#suffix'] = '</div>';
    $form['#attached']['library'][] = 'person_info_form/person_form_styles';
    $form['#attached']['library'][] = 'person_info_form/enhanced_multiselect';
  
    $form['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First name'),
      '#required' => TRUE,
      '#attributes' => ['placeholder' => $this->t('Enter first name')],
    ];
  
    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last name'),
      '#required' => TRUE,
      '#attributes' => ['placeholder' => $this->t('Enter last name')],
    ];
  
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#required' => TRUE,
      '#attributes' => ['placeholder' => $this->t('Enter address')],
    ];
  
    $form['phone_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Phone type'),
      '#options' => [
        '' => $this->t('- Select -'),
        'home' => $this->t('Home'),
        'business' => $this->t('Business'),
        'mobile' => $this->t('Mobile'),
      ],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::toggleAgreeCheckbox',
        'wrapper' => 'agree-wrapper',
      ],
    ];
  
    $form['phone_number'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone number'),
      '#required' => TRUE,
      '#attributes' => ['placeholder' => $this->t('Enter phone number')],
    ];
  
    // Inject the agree checkbox here, between phone number and favorite color
    $form['agree_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'agree-wrapper'],
    ];
  
    if ($form_state->getValue('phone_type') === 'mobile') {
      $form['agree_wrapper']['agree'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('I agree to receiving SMS messages'),
      ];
    }
  
    $form['favorite_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Favorite color(s)'),
      '#multiple' => TRUE,
      '#options' => [
        'red' => $this->t('Red'),
        'blue' => $this->t('Blue'),
        'green' => $this->t('Green'),
        'yellow' => $this->t('Yellow'),
        'other' => $this->t('Other'),
      ],
      // '#required' => TRUE,
      '#attributes' => ['class' => ['enhanced-multiselect']],
    ];
  
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];
  
    return $form;
  }
  
  /**
   * AJAX callback for showing/hiding agree checkbox.
   */
  public function toggleAgreeCheckbox(array &$form, FormStateInterface $form_state) {
    return $form['agree_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
  
    // Basic structure check
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('email', $this->t('Please enter a valid email address.'));
    }
    // Custom stricter check: ensure dot exists after @
    elseif (!preg_match('/@[^\\s@]+\\.[^\\s@]+$/', $email)) {
      $form_state->setErrorByName('email', $this->t('Email address must contain a domain with a period.'));
    }
  
    // Phone number check (10 digits only)
    $phone_raw = $form_state->getValue('phone_number');
    $phone_clean = preg_replace('/[^0-9]/', '', $phone_raw);
    if (strlen($phone_clean) !== 10) {
      $form_state->setErrorByName('phone_number', $this->t('Phone number must be exactly 10 digits.'));
    }
  
    // At least 2 favorite colors
    $colors = $form_state->getValue('favorite_color') ?? [];
    if (count($colors) < 2) {
      $form_state->setErrorByName('favorite_color', $this->t('Please select at least two favorite colors.'));
    }
  }
      
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Log the data.
    \Drupal::logger('person_info_form')->notice('Form submission: @data', ['@data' => print_r($values, TRUE)]);

    // Store in DB (custom table).
    \Drupal::database()->insert('person_info_form_submissions')
      ->fields([
        'first_name' => $values['first_name'],
        'last_name' => $values['last_name'],
        'email' => $values['email'],
        'phone_type' => $values['phone_type'],
        'phone_number' => $values['phone_number'],
        'favorite_color' => implode(', ', $values['favorite_color']),
        'agree' => $values['agree'] ?? 0,
        'submitted' => \Drupal::time()->getCurrentTime(),
      ])->execute();

    $this->messenger()->addStatus($this->t('Your information has been submitted.'));
  }
}
