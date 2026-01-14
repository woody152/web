from datetime import datetime, timezone, timedelta

# Check if the current date is within daylight saving time period for Eastern Time Zone (2nd Sunday of March to 1st Sunday of November)
def is_daylight_saving_time(dt, utc):
    start_dst = datetime(dt.year, 3, 8, 2, 0, tzinfo=utc)  # 2nd Sunday of March
    end_dst = datetime(dt.year, 11, 1, 2, 0, tzinfo=utc)  # 1st Sunday of November
    return start_dst <= dt < end_dst

def GetExchangeTime(strName = 'NYSE'):
    # Define the UTC timezone
    utc = timezone.utc
    # Get the current UTC time
    now_utc = datetime.now(utc)
    if strName == 'NYSE':
        iHours = -5
        # Determine the Eastern Time Zone offset considering daylight saving time
        if is_daylight_saving_time(now_utc, utc):
            #eastern_offset = timedelta(hours=-4)  # Eastern Daylight Time (EDT) offset
            iHours += 1
    elif strName == 'SZSE':
        iHours = 8
    eastern_offset = timedelta(hours=iHours)  # Eastern Standard Time (EST) offset
    # Convert UTC time to Eastern Time Zone
    now_ny = now_utc.astimezone(timezone(eastern_offset))
    # Extract the hour and minute in 'America/New_York' time zone
    iTime = now_ny.hour * 100 + now_ny.minute 
    return iTime

def GetBeijingTimeDisplay():
    utc8 = timezone(timedelta(hours=8))
    beijing_time = datetime.now(utc8)
    return beijing_time.strftime("%H:%M:%S")
