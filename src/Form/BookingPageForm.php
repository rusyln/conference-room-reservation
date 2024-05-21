<?php

namespace Drupal\conference_room_reservation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;

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
  public function buildForm(array $form, FormStateInterface $form_state, $room_id = NULL) {
    // If room_id is not provided, redirect to booking page
    if (!$room_id) {
      $form_state->setRedirect('conference_room_reservation.booking_page');
      return;
    }

    // Get room title for display
    $room_title = Node::load($room_id)->getTitle();

    $form['room_title'] = [
      '#type' => 'item',
      '#markup' => $this->t('Room: @title', ['@title' => $room_title]),
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

    $form['room_id'] = [
      '#type' => 'value',
      '#value' => $room_id,
    ];

    return $form;
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
