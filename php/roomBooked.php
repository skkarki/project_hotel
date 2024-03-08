<?php
// Database connection setup
include 'db.php';

// Get the POST data
$roomID = mysqli_real_escape_string($conn, $_POST['RoomID']);
$maxGuest = mysqli_real_escape_string($conn, $_POST['MaxGuest']);
$checkOut = mysqli_real_escape_string($conn, $_POST['CheckOut']);
$checkIn = mysqli_real_escape_string($conn, $_POST['CheckIn']); // Fixed variable name case
$fullName = mysqli_real_escape_string($conn, $_POST['FullName']);
$phone = mysqli_real_escape_string($conn, $_POST['Phone']);
$verifyID = mysqli_real_escape_string($conn, $_POST['VerifyID']);
$country = mysqli_real_escape_string($conn, $_POST['Country']);
$email = mysqli_real_escape_string($conn, $_POST['Email']);
$roomReadyToBook = true;
$checkingmessage = "";

// Check if the calendar table exists
$sql = "SELECT 1 FROM calendar LIMIT 1";
$result = mysqli_query($conn, $sql);
if (mysqli_num_rows($result) === 0) {
    throw new Exception("calendar table does not exist.");
}

// Check if the room is available
$sql = "SELECT * FROM calendar WHERE RoomId = '$roomID' AND (Date >= '$checkIn' AND Date < '$checkOut')";
$result = mysqli_query($conn, $sql);

$availableRooms = array();
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['Availabilities'] > 0) {
        $availableRooms[] = $row;
    } else {
        $roomReadyToBook = false;
        break; // No need to continue if any room is not available
    }
}

// If the room is available, update the calendar table
if ($roomReadyToBook === true) {
    // Decrease the availability by 1
    $calendarsql = "UPDATE calendar
            SET Availabilities = Availabilities - 1
            WHERE RoomId = '$roomID'
            AND Date >= '$checkIn'
            AND Date < '$checkOut'";

    mysqli_query($conn, $calendarsql);

    // Insert guest details into Guest table
    $insertGuestQuery = "INSERT INTO guests (FullName, Phone, ID_Verification, Country, Email) VALUES ('$fullName', '$phone', '$verifyID', '$country', '$email')";
    if (mysqli_query($conn, $insertGuestQuery)) {
        $guestID = mysqli_insert_id($conn); // Get the last inserted guest ID

        // Insert reservation details into Reservation table
        $insertReservationQuery = "INSERT INTO reservations (GuestID, RoomID, CheckInDate, CheckOutDate) VALUES ('$guestID', '$roomID', '$checkIn', '$checkOut')";
        if (mysqli_query($conn, $insertReservationQuery)) {
            $response['success'] = "Room booked successfully!";
        } else {
            $response['error'] = "Failed to insert reservation details.";
        }
    } else {
        $response['error'] = "Failed to insert guest details.";
    }
} else {
    $response['error'] = "Room Not Available. Try Again With Different Date And Room.";
}
echo json_encode($response);

$conn->close();
