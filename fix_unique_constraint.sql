USE psII;

UPDATE employees SET reference_no = NULL WHERE reference_no = '' OR reference_no IS NULL;


ALTER TABLE employees DROP INDEX reference_no;


ALTER TABLE employees MODIFY COLUMN reference_no VARCHAR(50) DEFAULT NULL;


ALTER TABLE employees ADD UNIQUE KEY unique_reference_no (reference_no);


UPDATE employees SET ni_number = NULL WHERE ni_number = '';
ALTER TABLE employees MODIFY COLUMN ni_number VARCHAR(50) DEFAULT NULL;

UPDATE employees SET student_ref = NULL WHERE student_ref = '';
ALTER TABLE employees MODIFY COLUMN student_ref VARCHAR(50) DEFAULT NULL;

SELECT 'Migration completed successfully!' as status;
