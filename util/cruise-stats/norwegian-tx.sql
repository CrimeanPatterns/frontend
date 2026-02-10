select
 date_format(h.PostingDate, '%Y-%m') as Date,
 sum(case when h.Amount > 0 then 1 else 0 end) as PurchasesCount,
 sum(case when h.Amount > 0 then h.Amount else 0 end) as PurchasesTotal,
 sum(case when h.Amount < 0 then 1 else 0 end) as RefundsCount,
 sum(case when h.Amount < 0 then h.Amount else 0 end) as RefundsTotal
from
 AccountHistory h
where
 h.PostingDate >= '2019-04-01' and h.PostingDate < '2021-04-01'
 and h.Category not like '%restaurant%' and h.Category not like '%merchan%'
 and h.Description like '%norweg%'
group by
 date_format(h.PostingDate, '%Y-%m')