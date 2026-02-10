function override_timestamp(tag, timestamp, record)
    if record and record["microtime"] and not (record["microtime"] == nil)  then
        return 1, record["microtime"], record
    end

    return 0, timestamp, record
end