<?php

namespace Drupal\conference_room_reservation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\conference_room_reservation\Form\BookingPageForm;
use Drupal\node\Entity\Node;
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
    $room = Node::load($room_id);
  
    // Check if the form is submitted
    if ($request->isMethod('post')) {
      // Get booking information from the form submission
      $values = $request->request->all();
  
      // Ensure the necessary form fields are set
      if (isset($values['start_datetime']) && isset($values['end_datetime'])) {
        $start_datetime = $values['start_datetime'];
        $end_datetime = $values['end_datetime'];
  
        // Check room availability
        if (!BookingPageForm::checkRoomAvailability($room_id, $start_datetime, $end_datetime)) {
          $this->messenger()->addError($this->t('The selected room is not available for the specified time period.'));
          // Redirect back to the booking page
          return new RedirectResponse('/conference-room-booking');
        }
  
        // Save the booking information
        // This is a simplified example; in a real application, you'd save this in a custom entity or a similar data structure.
        $this->messenger()->addStatus($this->t('Room booked successfully from @start to @end', [
          '@start' => $start_datetime,
          '@end' => $end_datetime,
        ]));
  
        // Redirect back to the conference room list
        return new RedirectResponse('/conference-room');
      } else {
        $this->messenger()->addError($this->t('Both start and end date and time must be provided.'));
        return new RedirectResponse('/conference-room-booking');
      }
    }
  
    // Display the booking form if the request is not a POST request
    return $this->redirect('conference_room_reservation.booking_page');
  }

  public function bookedRooms() {
    // Query the 'booking' nodes to fetch booked rooms data
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'booking')
      ->accessCheck(TRUE);
    $nids = $query->execute();
    $booked_rooms = \Drupal\node\Entity\Node::loadMultiple($nids);

    // Prepare data to pass to the Twig template
    $rows = [];
    foreach ($booked_rooms as $booking) {
      $room_id = $booking->get('field_room')->target_id;
      $room = Node::load($room_id);
      $room_title = $room->getTitle();
      $start_datetime = $booking->get('field_start_datetime')->value;
      $end_datetime = $booking->get('field_end_datetime')->value;

      $rows[] = [
        'room_title' => $room_title,
        'start_datetime' => $start_datetime,
        'end_datetime' => $end_datetime,
      ];
    }

    return [
      '#theme' => 'booked_rooms_page',
      '#booked_rooms' => $rows,
    ];
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
