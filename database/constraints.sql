-- constraint to validate a fine >= 0
ALTER TABLE fines
ADD CONSTRAINT chk_fine_non_negative
CHECK (amount >= 0);

-- constraint to check that if the fine status is payed force the insert of payment date
ALTER TABLE fines
ADD CONSTRAINT chk_fines_payment_date
CHECK (
    (status = 'Pagada' AND payment_date IS NOT NULL)
    OR
    (status <> 'Pagada')
);

-- constraint to validate due date >= loan date
ALTER TABLE loans
ADD CONSTRAINT chk_dates_valid
CHECK (return_deadline >= start_date);


-- constraint to validate to ensure every fine has an id_loan
ALTER TABLE fines ADD CONSTRAINT chk_fines_needs_loan 
CHECK (id_loan IS NOT NULL);