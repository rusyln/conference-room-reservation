<?php

namespace Drupal\conference_room_reservation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class BookingPageForm extends FormBase {

  public function getFormId() {
    return 'conference_room_booking_page_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load all conference rooms
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'conference_room')
      ->condition('status', 1)
      ->accessCheck(TRUE);
    $nids = $query->execute();
    $rooms = Node::loadMultiple($nids);

    // Prepare options for the room select field
    $options = [];
    foreach ($rooms as $room) {
      $options[$room->id()] = $room->getTitle();
    }

    $form['room_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Room'),
      '#options' => $options,
      '#required' => TRUE,
    ];

    $form['date'] = [
      '#type' => 'date',
      '#title' => $this->t('Booking Date'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Book Room'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $room_id = $form_state->getValue('room_id');
    $date = $form_state->getValue('date');

    // Save the booking information
    // This is a simplified example; in a real application, you'd save this in a custom entity or a similar data structure.
    \Drupal::messenger()->addStatus($this->t('Room @room booked for @date', [
      '@room' => Node::load($room_id)->getTitle(),
      '@date' => $date,
    ]));

    // Redirect back to the booking page
    $form_state->setRedirect('conference_room_reservation.booking_page');
  }
}
