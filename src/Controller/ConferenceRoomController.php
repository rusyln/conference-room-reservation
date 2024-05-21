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
    // Check room availability before loading the form
    if (!$this->checkRoomAvailability($room_id)) {
      \Drupal::messenger()->addError('The selected room is not available.');
      return new RedirectResponse(Url::fromRoute('conference_room_reservation.booking_page')->toString());
    }

    // Load the booking form
    $form = $this->formBuilder()->getForm(BookingPageForm::class, $room_id);

    return [
      '#theme' => 'conference_room_book_page',
      '#form' => $form,
    ];
  }

  /**
   * Helper function to check if the room is available for booking.
   */
  private function checkRoomAvailability($room_id) {
    // Implement your logic to check room availability here.
    // For example, you might check if there are any existing bookings for the room.
    // Return TRUE if the room is available, FALSE otherwise.
    return TRUE; // Placeholder, replace with your actual logic
  }

  public function bookingPage() {
    // Load the booking form without a specific room
    $form = $this->formBuilder()->getForm(BookingPageForm::class);

    return [
      '#theme' => 'conference_room_book_page',
      '#form' => $form,
    ];
  }

}
