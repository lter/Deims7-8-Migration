
library(dplyr)
library(tidyr)

df_assoc_party_all <- read.csv("exportAssocParty.csv", header = T, as.is = T)

df_assoc_party <- select(df_assoc_party_all, dataset_id, person_id)

df_assoc_party <- arrange(df_assoc_party, dataset_id)

df_dataset_ids <- read.csv("exportDatasetIDs.csv", header = T, as.is = T)

df_ref_assoc_dataset <- setNames(data.frame(matrix(ncol = 3, nrow = 0)), c("dataset_id", "delta", "person_id"))

i <- 1

while (i <= nrow(df_assoc_party)) {
  
  dataset_id_1 <- df_assoc_party[i,1]
  
  print(df_assoc_party[i,1])
  
  df_ref_sub <- filter(df_assoc_party, dataset_id == dataset_id_1)
  
  max_delta <- nrow(df_ref_sub) - 1
  
  df_ref_sub$delta <- 0:max_delta
  
  df_ref_assoc_dataset <- rbind(df_ref_assoc_dataset, df_ref_sub)
  
  i = i + nrow(df_ref_sub)
  
}

df_asparty_dataset <- left_join(df_ref_assoc_dataset, df_dataset_ids, by = c("dataset_id" = "nid"))

df_asparty_dataset$bundle <- "data_set"
df_asparty_dataset$langcode <- "en"



df_asparty_dataset <- select(df_asparty_dataset, bundle, entity_id = dataset_id, revision_id = vid, langcode, delta, field_data_set_assoc_party_target_id = person_id)

write.csv(df_asparty_dataset, file = "uploadAssocPartyDataset.csv", row.names = F)
