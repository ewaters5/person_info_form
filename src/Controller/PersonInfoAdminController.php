<?php

namespace Drupal\person_info_form\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Link;

class PersonInfoAdminController extends ControllerBase {

  public function report() {
    $header = [
        'id' => $this->t('ID'),
        'name' => $this->t('Name'),
        'email' => $this->t('Email'),
        'phone' => $this->t('Phone'),
        'colors' => $this->t('Favorite Colors'),
        'submitted' => $this->t('Submitted'),
        'operations' => $this->t('Operations'),
    ];

    $rows = [];
    $results = Database::getConnection()->select('person_info_form_submissions', 'p')
      ->fields('p', ['id', 'first_name', 'last_name', 'email', 'phone_type', 'phone_number', 'favorite_color', 'submitted'])
      ->orderBy('submitted', 'DESC')
      ->execute();

      foreach ($results as $record) {
        $delete_url = Url::fromRoute('person_info_form.delete_submission_confirm', ['id' => $record->id]);
        $delete_link = Link::fromTextAndUrl($this->t('Delete'), $delete_url)->toString();
      
        $rows[] = [
          'id' => $record->id,
          'name' => $record->first_name . ' ' . $record->last_name,
          'email' => $record->email,
          'phone' => ucfirst($record->phone_type) . ': ' . $record->phone_number,
          'colors' => $record->favorite_color,
          'submitted' => \Drupal::service('date.formatter')->format($record->submitted, 'short'),
          'operations' => ['data' => ['#markup' => $delete_link]],
        ];
    }
    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No submissions found.'),
    ];
  }

  public function deleteSubmission($id) {
    $connection = \Drupal::database();
  
    $connection->delete('person_info_form_submissions')
      ->condition('id', $id)
      ->execute();
  
    \Drupal::messenger()->addMessage($this->t('Submission @id has been deleted.', ['@id' => $id]));
  
    return new RedirectResponse(Url::fromRoute('person_info_form.admin_submissions')->toString());
  }

}
