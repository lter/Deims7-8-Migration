

library(tidyr)
library(dplyr)
library(stringr)


df_standard_units <- read.csv("units.csv", header = T, as.is = T)

df_raw <- read.csv("variableExport_codeID.csv", header = T, as.is = T)

#subset for variables with units (physical)

df_units_raw <- filter(df_raw, field_variables_type == "physical")
df_units_raw <- select(df_units_raw, variables_data, field_variables_id)

df_units_all <- setNames(data.frame(matrix(ncol = 2, nrow = 0)), c("variable_id", "unit"))

for (i in 1:nrow(df_units_raw)) {
  
  variable_id <- df_units_raw[i,2]
  
  raw_string <- df_units_raw[i,1]
  
  unit_string <- str_split_fixed(raw_string, "s:22:\"data_explorer_settings\"", n = 2)
  
  unit_string_split <- str_split_fixed(unit_string[1,1], "s:\\d+:", n = Inf)
  
  unit_text_split <- str_split_fixed(unit_string_split[1,3], "\"", n = Inf)
  unit_text <- unit_text_split[1,2]
  
  #use this to clean up units or delete whole statement
  
  unit_text <- case_when(
    unit_text == "celcius" ~ "celsius",
    unit_text == "centiMeterPerYear" ~ "centimeterPerYear",
    unit_text == "Decimal Degrees" ~ "degree",
    unit_text == "inches" ~ "inch",
    unit_text == "kilogramsPerCubicMeter" ~ "kilogramPerCubicMeter",
    unit_text == "kilogramsPerCubicMeters" ~ "kilogramPerCubicMeter",
    unit_text == "kiloPascals" ~ "kilopascal",
    unit_text == "meters" ~ "meter",
    unit_text == "microGramPerGram" ~ "microgramsPerGram",
    unit_text == "microgramPerGram" ~ "microgramsPerGram",
    unit_text == "microgramPerLiter" ~ "microgramsPerLiter",
    unit_text == "microMolePerMeterSqauredPerSecond" ~ "micromolesPerMeterSquaredPerSecond",
    unit_text == "micromolesPerSquareMeterPerSecond" ~ "micromolesPerMeterSquaredPerSecond",
    unit_text == "microSiemenPerCentimeter" ~ "microSiemensPerCentimeter",
    unit_text == "milePerHour" ~ "milesPerHour",
    unit_text == "millibars" ~ "millibar",
    unit_text == "milliBars" ~ "millibar",
    unit_text == "milligramPerLiter" ~ "milligramsPerLiter",
    unit_text == "millimeterPerYear" ~ "millimetersPerYear",
    unit_text == "millimeterPerSecond" ~ "millimetersPerSecond",
    unit_text == "nominalHours" ~ "nominalHour",
    unit_text == "square meter" ~ "squareMeter",
    unit_text == "wattPerMeterSquared" ~ "wattsPerMeterSquared",
    unit_text == "WattsPerMeterSquared" ~ "wattsPerMeterSquared",
    unit_text == "wattsPerSquareMeter" ~ "wattsPerMeterSquared",
    unit_text == "WattsPerSquareMeter" ~ "wattsPerMeterSquared",
    TRUE ~ as.character(unit_text)
  )

  df_units_row <- data.frame("variable_id" = variable_id,
                                 "unit" = unit_text)
  
  df_units_all <- rbind(df_units_all, df_units_row)
  
  
}

# this now has variable_id and unit
# not necessary to write out to file, but good to look at

# write.csv(df_units_all, file = "variableUnits.csv", row.names = F)

# build file for generating unit nodes by combining standard units with used custom units

df_units_used <- distinct(df_units_all, name = unit)

df_units_used_standard <- full_join(df_standard_units, df_units_used, by = "name")

df_units_used_standard <- select(df_units_used_standard, -id)

#if you want to control nid you need to know what you are doing.
#if two uploads have the same nid the records will just be overwritten
#I am using 5000 - 10000 for units

df_units_used_standard$nid <-  1:nrow(df_units_used_standard)
df_units_used_standard$nid <- df_units_used_standard$nid + 5000

#before uploading to Drupal remember to take out special character

write.csv(df_units_used_standard, file = "uploadUnitNode.csv", row.names = F, na = "")

df_make_link <- select(df_units_used_standard, unit = name, nid)

df_units_variables <- left_join(df_units_all, df_make_link, by = "unit")

df_units_variables <- select(df_units_variables, unit_id = nid, field_variables_id = variable_id)

df_raw_unit <- left_join(df_raw, df_units_variables, by = "field_variables_id")

write.csv(df_raw_unit, file = "variableExport_code_unit.csv", row.names = F, na = "")
