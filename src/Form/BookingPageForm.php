<?php

namespace Drupal\conference_room_reservation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class BookingPageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conference_room_booking_page_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load all conference rooms
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'conference_room')
      ->condition('status', 1)
      ->accessCheck(TRUE);
    $nids = $query->execute();
    $rooms = Node::loadMultiple($nids);


// Load all conference rooms
$nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'conference_room', 'status' => 1]);

// Prepare options for the room select field
$options = [];
foreach ($nodes as $node) {
  $options[$node->id()] = $node->getTitle();
}


    $form['room_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Room'),
      '#options' => $options,
      '#required' => TRUE,
    ];

    $form['start_datetime'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Start Date and Time'),
      '#date_timezone' => date_default_timezone_get(),
      '#date_date_element' => 'date',
      '#date_time_element' => 'time',
      '#date_format' => 'Y-m-d H:i',
      '#required' => TRUE,
    ];

    $form['end_datetime'] = [
      '#type' => 'datetime',
      '#title' => $this->t('End Date and Time'),
      '#date_timezone' => date_default_timezone_get(),
      '#date_date_element' => 'date',
      '#date_time_element' => 'time',
      '#date_format' => 'Y-m-d H:i',
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Book Room'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $start_datetime = $form_state->getValue('start_datetime');
    $end_datetime = $form_state->getValue('end_datetime');

    if ($start_datetime >= $end_datetime) {
      $form_state->setErrorByName('end_datetime', $this->t('The end date and time must be after the start date and time.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $room_id = $form_state->getValue('room_id');
    $start_datetime = $form_state->getValue('start_datetime');
    $end_datetime = $form_state->getValue('end_datetime');

    // Save the booking information
    // This is a simplified example; in a real application, you'd save this in a custom entity or a similar data structure.
    \Drupal::messenger()->addStatus($this->t('Room @room booked from @start_datetime to @end_datetime', [
      '@room' => Node::load($room_id)->getTitle(),
      '@start_datetime' => $start_datetime,
      '@end_datetime' => $end_datetime,
    ]));

    // Redirect back to the booking page
    $form_state->setRedirect('conference_room_reservation.booking_page');
  }
}
