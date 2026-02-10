SET SQL_MODE ="";
SELECT
  ac.PostingDate,
  ac.Description,
  ac.Miles

FROM
  AccountHistory ac
  INNER JOIN Account a on a.AccountID = ac.AccountID

WHERE
  a.ProviderID = 22 AND ac.Description is not null
  AND Description NOT LIKE '%Go More%'
  AND Description NOT LIKE '%milestone%'
  AND Description NOT LIKE '%hotel dining offer%'
  AND ac.PostingDAte > '2019-01-01'

GROUP BY
  ac.Description

LIMIT 1000