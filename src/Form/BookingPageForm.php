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
    // Form elements to select room and specify booking duration.
    $form['room_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Room'),
      '#options' => $this->getRoomOptions(),
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

    $room_id = $form_state->getValue('room_id');
    $is_available = self::checkRoomAvailability($room_id, $start_datetime, $end_datetime);
    if (!$is_available) {
      $form_state->setErrorByName('room_id', $this->t('The selected room is not available for the specified time period.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $room_id = $form_state->getValue('room_id');
    $start_datetime = $form_state->getValue('start_datetime');
    $end_datetime = $form_state->getValue('end_datetime');

    // Create a new booking node
    $node = Node::create([
      'type' => 'booking',
      'title' => $this->t('Booking for @room', ['@room' => Node::load($room_id)->getTitle()]),
      'field_room' => $room_id,
      'field_start_datetime' => $start_datetime,
      'field_end_datetime' => $end_datetime,
    ]);
    $node->save();

    \Drupal::messenger()->addStatus($this->t('Room @room booked from @start_datetime to @end_datetime', [
      '@room' => Node::load($room_id)->getTitle(),
      '@start_datetime' => $start_datetime,
      '@end_datetime' => $end_datetime,
    ]));

    $form_state->setRedirect('conference_room_reservation.booking_page');
  }

  private function getRoomOptions() {
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'conference_room', 'status' => 1]);
    $options = [];
    foreach ($nodes as $node) {
      $options[$node->id()] = $node->getTitle();
    }
    return $options;
  }

  public static function checkRoomAvailability($room_id, $start_datetime, $end_datetime) {
    $start_timestamp = strtotime($start_datetime);
    $end_timestamp = strtotime($end_datetime);

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'booking')
      ->condition('field_room_id', $room_id)
      ->condition('field_start_datetime', $end_timestamp, '<')
      ->condition('field_end_datetime', $start_timestamp, '>')
      ->accessCheck(TRUE);
    $result = $query->execute();

    return empty($result);
  }
}
