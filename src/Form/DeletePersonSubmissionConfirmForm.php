<?php

namespace Drupal\person_info_form\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;

class DeletePersonSubmissionConfirmForm extends ConfirmFormBase {

  protected $id;

  public function getFormId() {
    return 'person_info_form_delete_submission_confirm';
  }

  public function getQuestion() {
    return $this->t('Are you sure you want to delete submission #@id?', ['@id' => $this->id]);
  }

  public function getCancelUrl() {
    return Url::fromRoute('person_info_form.admin_submissions');
  }

  public function getConfirmText() {
    return $this->t('Delete');
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $this->id = $id;
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $connection = Database::getConnection();
    $connection->delete('person_info_form_submissions')
      ->condition('id', $this->id)
      ->execute();

    $this->messenger()->addMessage($this->t('Submission @id has been deleted.', ['@id' => $this->id]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
