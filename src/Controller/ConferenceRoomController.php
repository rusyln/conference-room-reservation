<?php

namespace Drupal\conference_room_reservation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Link;
use Drupal\Core\Url;

class ConferenceRoomController extends ControllerBase {

  public function view() {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'conference_room')
      ->condition('status', 1)
      ->accessCheck(TRUE); // Ensure access check is explicitly set

    $nids = $query->execute();
    $rooms = \Drupal\node\Entity\Node::loadMultiple($nids);

    $rows = [];
    foreach ($rooms as $room) {
      $link = Link::fromTextAndUrl('Book', Url::fromRoute('conference_room_reservation.book', ['room_id' => $room->id()]))->toString();
      $rows[] = [
        'data' => [
          $room->getTitle(),
          $room->field_capacity->value,
          $room->field_availability->value,
          $link,
        ],
      ];
    }

    $header = ['Room', 'Capacity', 'Availability', 'Actions'];
    $build = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;
  }

  public function book($room_id, Request $request) {
    $room = \Drupal\node\Entity\Node::load($room_id);
  
    // Check if the form is submitted
    if ($request->isMethod('post')) {
      // Get booking information from the form submission
      $values = $request->request->all();
  
      // Ensure the necessary form fields are set
      if (isset($values['start_datetime'][0]['value']) && isset($values['end_datetime'][0]['value'])) {
        $start_datetime = $values['start_datetime'][0]['value'];
        $end_datetime = $values['end_datetime'][0]['value'];
  
        // Check room availability
        if (!$this->checkRoomAvailability($room_id, $start_datetime, $end_datetime)) {
          \Drupal::messenger()->addError('The selected room is not available for the specified time period.');
          // Redirect back to the booking page
          return new RedirectResponse('/conference-room-booking');
        }
  
        // Save the booking information
        // This is a simplified example; in a real application, you'd save this in a custom entity or a similar data structure.
        \Drupal::messenger()->addStatus('Room booked successfully from ' . $start_datetime . ' to ' . $end_datetime);
  
        // Redirect back to the conference room list
        return new RedirectResponse('/conference-room');
      } else {
        \Drupal::messenger()->addError('Both start and end date and time must be provided.');
        return new RedirectResponse('/conference-room-booking');
      }
    }
  
    // Load the booking form
    $form = \Drupal::formBuilder()->getForm('Drupal\conference_room_reservation\Form\BookingPageForm');
  
    return [
      '#theme' => 'conference_room_book_page',
      '#room' => $room,
      '#form' => $form,
    ];
  }
  

/**
 * Helper function to check if the room is available for booking.
 */
private function checkRoomAvailability($room_id, $start_datetime, $end_datetime) {
  // Load all bookings for the selected room within the specified time range.
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'booking')
    ->condition('status', 1)
    ->condition('field_room_reference', $room_id)
    ->condition('field_end_datetime', $start_datetime, '>')
    ->condition('field_start_datetime', $end_datetime, '<')
    ->accessCheck(TRUE);
  $result = $query->execute();

  // If there are any overlapping bookings, the room is not available.
  return empty($result);
}

  public function bookingPage() {
    // Load the booking form without a specific room
    $form = \Drupal::formBuilder()->getForm('Drupal\conference_room_reservation\Form\BookingPageForm');

    return [
      '#theme' => 'conference_room_book_page',
      '#form' => $form,
    ];
  }
}
