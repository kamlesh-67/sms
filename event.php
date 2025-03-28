<?php
class EventServices {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function createEvent($eventData) {
        $title = $this->db->escapeString($eventData['title']);
        $description = $this->db->escapeString($eventData['description']); 
        $date = $this->db->escapeString($eventData['date']);
        $time = $this->db->escapeString($eventData['time']);
        $location = $this->db->escapeString($eventData['location']);
        $capacity = (int)$eventData['capacity'];
        $organizer = $this->db->escapeString($eventData['organizer']);
        $type = $this->db->escapeString($eventData['type']);

        $query = "INSERT INTO events (title, description, event_date, event_time, 
                location, capacity, organizer, event_type, created_at)
                VALUES ('$title', '$description', '$date', '$time', '$location', 
                $capacity, '$organizer', '$type', NOW())";
        
        return $this->db->execute($query);
    }

    public function updateEvent($eventId, $eventData) {
        $title = $this->db->escapeString($eventData['title']);
        $description = $this->db->escapeString($eventData['description']);
        $date = $this->db->escapeString($eventData['date']);
        $time = $this->db->escapeString($eventData['time']);
        $location = $this->db->escapeString($eventData['location']);
        $capacity = (int)$eventData['capacity'];
        $organizer = $this->db->escapeString($eventData['organizer']);
        $type = $this->db->escapeString($eventData['type']);

        $query = "UPDATE events 
                SET title = '$title',
                    description = '$description',
                    event_date = '$date',
                    event_time = '$time',
                    location = '$location',
                    capacity = $capacity,
                    organizer = '$organizer',
                    event_type = '$type',
                    updated_at = NOW()
                WHERE event_id = $eventId";

        return $this->db->execute($query);
    }

    public function deleteEvent($eventId) {
        $query = "DELETE FROM events WHERE event_id = $eventId";
        return $this->db->execute($query);
    }

    public function getEventById($eventId) {
        $query = "SELECT * FROM events WHERE event_id = $eventId";
        $result = $this->db->select($query);
        return $result->fetch_assoc();
    }

    public function getAllEvents() {
        $query = "SELECT * FROM events ORDER BY event_date DESC";
        return $this->db->select($query);
    }

    public function getUpcomingEvents() {
        $query = "SELECT * FROM events 
                WHERE event_date >= CURDATE() 
                ORDER BY event_date ASC";
        return $this->db->select($query);
    }

    public function searchEvents($searchTerm) {
        $searchTerm = $this->db->escapeString($searchTerm);
        $query = "SELECT * FROM events 
                WHERE title LIKE '%$searchTerm%'
                OR description LIKE '%$searchTerm%'
                OR location LIKE '%$searchTerm%'
                OR event_type LIKE '%$searchTerm%'";
        return $this->db->select($query);
    }

    public function getEventsByType($type) {
        $type = $this->db->escapeString($type);
        $query = "SELECT * FROM events 
                WHERE event_type = '$type' 
                ORDER BY event_date DESC";
        return $this->db->select($query);
    }

    public function getEventAttendees($eventId) {
        $query = "SELECT s.* FROM students s
                JOIN event_registrations er ON s.student_id = er.student_id
                WHERE er.event_id = $eventId";
        return $this->db->select($query);
    }

    public function getEventCapacityStatus($eventId) {
        $query = "SELECT e.capacity, COUNT(er.registration_id) as registered
                FROM events e
                LEFT JOIN event_registrations er ON e.event_id = er.event_id
                WHERE e.event_id = $eventId
                GROUP BY e.event_id";
        $result = $this->db->select($query);
        return $result->fetch_assoc();
    }
}

// Example usage:
/*
$eventServices = new EventServices();

// Create new event
$eventData = [
    'title' => 'Annual Tech Symposium',
    'description' => 'Annual technology symposium featuring industry experts',
    'date' => '2024-03-15',
    'time' => '09:00:00',
    'location' => 'Main Auditorium',
    'capacity' => 200,
    'organizer' => 'Computer Science Department',
    'type' => 'Academic'
];
$eventServices->createEvent($eventData);

// Get upcoming events
$upcomingEvents = $eventServices->getUpcomingEvents();

// Search events
$searchResults = $eventServices->searchEvents('tech');
*/
?>
