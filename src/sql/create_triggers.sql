DELIMITER //

CREATE TRIGGER after_part_insert 
AFTER INSERT ON parts
FOR EACH ROW
BEGIN
    IF NEW.category_id = 1 THEN  -- Anpassen an die tatsächliche Kategorie-ID für LEDs
        INSERT INTO led_mapping (part_id, led_position)
        VALUES (NEW.id, (
            SELECT COALESCE(MAX(led_position), 0) + 1
            FROM led_mapping
        ));
    END IF;
END //

CREATE TRIGGER before_part_delete
BEFORE DELETE ON parts
FOR EACH ROW
BEGIN
    DELETE FROM led_mapping WHERE part_id = OLD.id;
END //

DELIMITER ; 