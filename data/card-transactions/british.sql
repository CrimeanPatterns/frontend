SET SQL_MODE ="";
SELECT
  ac.PostingDate,
  ac.Description,
  ac.Miles

FROM
  AccountHistory ac
  INNER JOIN Account a on a.AccountID = ac.AccountID

WHERE
  a.ProviderID = 31 AND ac.Description is not null
  AND Description NOT LIKE '%booking ref%'
  AND ac.PostingDAte > '2019-01-01'

GROUP BY
  ac.Description

LIMIT 1000