-- ============================================================
-- Nigeria States and Local Government Areas (LGAs)
-- 36 States + Federal Capital Territory, 774 LGAs total
-- For use with NRMS schema: states(name, zone_id), local_governments(name, state_id)
-- Assumes geo_political_zones is already seeded with the 6 standard zone names
-- (as in database/schema.sql). Uses subselects so it does not depend on
-- specific auto-increment IDs.
-- ============================================================

USE nrms_db;

-- ------------------------------------------------------------
-- STATES (36 states + FCT), mapped to their geopolitical zone
-- ------------------------------------------------------------

-- North Central
INSERT INTO states (name, zone_id) SELECT 'Benue', id FROM geo_political_zones WHERE name = 'North Central';
INSERT INTO states (name, zone_id) SELECT 'Kogi', id FROM geo_political_zones WHERE name = 'North Central';
INSERT INTO states (name, zone_id) SELECT 'Kwara', id FROM geo_political_zones WHERE name = 'North Central';
INSERT INTO states (name, zone_id) SELECT 'Nasarawa', id FROM geo_political_zones WHERE name = 'North Central';
INSERT INTO states (name, zone_id) SELECT 'Niger', id FROM geo_political_zones WHERE name = 'North Central';
INSERT INTO states (name, zone_id) SELECT 'Plateau', id FROM geo_political_zones WHERE name = 'North Central';
INSERT INTO states (name, zone_id) SELECT 'Federal Capital Territory', id FROM geo_political_zones WHERE name = 'North Central';

-- North East
INSERT INTO states (name, zone_id) SELECT 'Adamawa', id FROM geo_political_zones WHERE name = 'North East';
INSERT INTO states (name, zone_id) SELECT 'Bauchi', id FROM geo_political_zones WHERE name = 'North East';
INSERT INTO states (name, zone_id) SELECT 'Borno', id FROM geo_political_zones WHERE name = 'North East';
INSERT INTO states (name, zone_id) SELECT 'Gombe', id FROM geo_political_zones WHERE name = 'North East';
INSERT INTO states (name, zone_id) SELECT 'Taraba', id FROM geo_political_zones WHERE name = 'North East';
INSERT INTO states (name, zone_id) SELECT 'Yobe', id FROM geo_political_zones WHERE name = 'North East';

-- North West
INSERT INTO states (name, zone_id) SELECT 'Jigawa', id FROM geo_political_zones WHERE name = 'North West';
INSERT INTO states (name, zone_id) SELECT 'Kaduna', id FROM geo_political_zones WHERE name = 'North West';
INSERT INTO states (name, zone_id) SELECT 'Kano', id FROM geo_political_zones WHERE name = 'North West';
INSERT INTO states (name, zone_id) SELECT 'Katsina', id FROM geo_political_zones WHERE name = 'North West';
INSERT INTO states (name, zone_id) SELECT 'Kebbi', id FROM geo_political_zones WHERE name = 'North West';
INSERT INTO states (name, zone_id) SELECT 'Sokoto', id FROM geo_political_zones WHERE name = 'North West';
INSERT INTO states (name, zone_id) SELECT 'Zamfara', id FROM geo_political_zones WHERE name = 'North West';

-- South East
INSERT INTO states (name, zone_id) SELECT 'Abia', id FROM geo_political_zones WHERE name = 'South East';
INSERT INTO states (name, zone_id) SELECT 'Anambra', id FROM geo_political_zones WHERE name = 'South East';
INSERT INTO states (name, zone_id) SELECT 'Ebonyi', id FROM geo_political_zones WHERE name = 'South East';
INSERT INTO states (name, zone_id) SELECT 'Enugu', id FROM geo_political_zones WHERE name = 'South East';
INSERT INTO states (name, zone_id) SELECT 'Imo', id FROM geo_political_zones WHERE name = 'South East';

-- South South
INSERT INTO states (name, zone_id) SELECT 'Akwa Ibom', id FROM geo_political_zones WHERE name = 'South South';
INSERT INTO states (name, zone_id) SELECT 'Bayelsa', id FROM geo_political_zones WHERE name = 'South South';
INSERT INTO states (name, zone_id) SELECT 'Cross River', id FROM geo_political_zones WHERE name = 'South South';
INSERT INTO states (name, zone_id) SELECT 'Delta', id FROM geo_political_zones WHERE name = 'South South';
INSERT INTO states (name, zone_id) SELECT 'Edo', id FROM geo_political_zones WHERE name = 'South South';
INSERT INTO states (name, zone_id) SELECT 'Rivers', id FROM geo_political_zones WHERE name = 'South South';

-- South West
INSERT INTO states (name, zone_id) SELECT 'Ekiti', id FROM geo_political_zones WHERE name = 'South West';
INSERT INTO states (name, zone_id) SELECT 'Lagos', id FROM geo_political_zones WHERE name = 'South West';
INSERT INTO states (name, zone_id) SELECT 'Ogun', id FROM geo_political_zones WHERE name = 'South West';
INSERT INTO states (name, zone_id) SELECT 'Ondo', id FROM geo_political_zones WHERE name = 'South West';
INSERT INTO states (name, zone_id) SELECT 'Osun', id FROM geo_political_zones WHERE name = 'South West';
INSERT INTO states (name, zone_id) SELECT 'Oyo', id FROM geo_political_zones WHERE name = 'South West';

-- ------------------------------------------------------------
-- LOCAL GOVERNMENT AREAS (774 total), mapped to their state
-- ------------------------------------------------------------

-- Benue (23 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Ado', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Agatu', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Apa', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Buruku', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Gboko', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Guma', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Gwer East', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Gwer West', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Katsina-Ala', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Konshisha', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Kwande', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Logo', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Makurdi', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Obi', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Ogbadibo', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Ohimini', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Oju', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Okpokwu', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Otukpo', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Tarka', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Ukum', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Ushongo', id FROM states WHERE name = 'Benue';
INSERT INTO local_governments (name, state_id) SELECT 'Vandeikya', id FROM states WHERE name = 'Benue';

-- Kogi (21 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Adavi', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Ajaokuta', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Ankpa', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Bassa', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Dekina', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Ibaji', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Idah', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Igalamela Odolu', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Ijumu', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Kabba/Bunu', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Kogi', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Lokoja', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Mopa Muro', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Ofu', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Ogori/Magongo', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Okehi', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Okene', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Olamaboro', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Omala', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Yagba East', id FROM states WHERE name = 'Kogi';
INSERT INTO local_governments (name, state_id) SELECT 'Yagba West', id FROM states WHERE name = 'Kogi';

-- Kwara (16 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Asa', id FROM states WHERE name = 'Kwara';
INSERT INTO local_governments (name, state_id) SELECT 'Baruten', id FROM states WHERE name = 'Kwara';
INSERT INTO local_governments (name, state_id) SELECT 'Edu', id FROM states WHERE name = 'Kwara';
INSERT INTO local_governments (name, state_id) SELECT 'Ekiti', id FROM states WHERE name = 'Kwara';
INSERT INTO local_governments (name, state_id) SELECT 'Ifelodun', id FROM states WHERE name = 'Kwara';
INSERT INTO local_governments (name, state_id) SELECT 'Ilorin East', id FROM states WHERE name = 'Kwara';
INSERT INTO local_governments (name, state_id) SELECT 'Ilorin South', id FROM states WHERE name = 'Kwara';
INSERT INTO local_governments (name, state_id) SELECT 'Ilorin West', id FROM states WHERE name = 'Kwara';
INSERT INTO local_governments (name, state_id) SELECT 'Irepodun', id FROM states WHERE name = 'Kwara';
INSERT INTO local_governments (name, state_id) SELECT 'Isin', id FROM states WHERE name = 'Kwara';
INSERT INTO local_governments (name, state_id) SELECT 'Kaiama', id FROM states WHERE name = 'Kwara';
INSERT INTO local_governments (name, state_id) SELECT 'Moro', id FROM states WHERE name = 'Kwara';
INSERT INTO local_governments (name, state_id) SELECT 'Offa', id FROM states WHERE name = 'Kwara';
INSERT INTO local_governments (name, state_id) SELECT 'Oke Ero', id FROM states WHERE name = 'Kwara';
INSERT INTO local_governments (name, state_id) SELECT 'Oyun', id FROM states WHERE name = 'Kwara';
INSERT INTO local_governments (name, state_id) SELECT 'Pategi', id FROM states WHERE name = 'Kwara';

-- Nasarawa (13 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Akwanga', id FROM states WHERE name = 'Nasarawa';
INSERT INTO local_governments (name, state_id) SELECT 'Awe', id FROM states WHERE name = 'Nasarawa';
INSERT INTO local_governments (name, state_id) SELECT 'Doma', id FROM states WHERE name = 'Nasarawa';
INSERT INTO local_governments (name, state_id) SELECT 'Karu', id FROM states WHERE name = 'Nasarawa';
INSERT INTO local_governments (name, state_id) SELECT 'Keana', id FROM states WHERE name = 'Nasarawa';
INSERT INTO local_governments (name, state_id) SELECT 'Keffi', id FROM states WHERE name = 'Nasarawa';
INSERT INTO local_governments (name, state_id) SELECT 'Kokona', id FROM states WHERE name = 'Nasarawa';
INSERT INTO local_governments (name, state_id) SELECT 'Lafia', id FROM states WHERE name = 'Nasarawa';
INSERT INTO local_governments (name, state_id) SELECT 'Nasarawa', id FROM states WHERE name = 'Nasarawa';
INSERT INTO local_governments (name, state_id) SELECT 'Nasarawa Egon', id FROM states WHERE name = 'Nasarawa';
INSERT INTO local_governments (name, state_id) SELECT 'Obi', id FROM states WHERE name = 'Nasarawa';
INSERT INTO local_governments (name, state_id) SELECT 'Toto', id FROM states WHERE name = 'Nasarawa';
INSERT INTO local_governments (name, state_id) SELECT 'Wamba', id FROM states WHERE name = 'Nasarawa';

-- Niger (25 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Agaie', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Agwara', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Bida', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Borgu', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Bosso', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Chanchaga', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Edati', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Gbako', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Gurara', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Katcha', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Kontagora', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Lapai', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Lavun', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Magama', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Mariga', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Mashegu', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Mokwa', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Moya', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Paikoro', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Rafi', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Rijau', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Shiroro', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Suleja', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Tafa', id FROM states WHERE name = 'Niger';
INSERT INTO local_governments (name, state_id) SELECT 'Wushishi', id FROM states WHERE name = 'Niger';

-- Plateau (17 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Barkin Ladi', id FROM states WHERE name = 'Plateau';
INSERT INTO local_governments (name, state_id) SELECT 'Bassa', id FROM states WHERE name = 'Plateau';
INSERT INTO local_governments (name, state_id) SELECT 'Bokkos', id FROM states WHERE name = 'Plateau';
INSERT INTO local_governments (name, state_id) SELECT 'Jos East', id FROM states WHERE name = 'Plateau';
INSERT INTO local_governments (name, state_id) SELECT 'Jos North', id FROM states WHERE name = 'Plateau';
INSERT INTO local_governments (name, state_id) SELECT 'Jos South', id FROM states WHERE name = 'Plateau';
INSERT INTO local_governments (name, state_id) SELECT 'Kanam', id FROM states WHERE name = 'Plateau';
INSERT INTO local_governments (name, state_id) SELECT 'Kanke', id FROM states WHERE name = 'Plateau';
INSERT INTO local_governments (name, state_id) SELECT 'Langtang North', id FROM states WHERE name = 'Plateau';
INSERT INTO local_governments (name, state_id) SELECT 'Langtang South', id FROM states WHERE name = 'Plateau';
INSERT INTO local_governments (name, state_id) SELECT 'Mangu', id FROM states WHERE name = 'Plateau';
INSERT INTO local_governments (name, state_id) SELECT 'Mikang', id FROM states WHERE name = 'Plateau';
INSERT INTO local_governments (name, state_id) SELECT 'Pankshin', id FROM states WHERE name = 'Plateau';
INSERT INTO local_governments (name, state_id) SELECT 'Qua''an Pan', id FROM states WHERE name = 'Plateau';
INSERT INTO local_governments (name, state_id) SELECT 'Riyom', id FROM states WHERE name = 'Plateau';
INSERT INTO local_governments (name, state_id) SELECT 'Shendam', id FROM states WHERE name = 'Plateau';
INSERT INTO local_governments (name, state_id) SELECT 'Wase', id FROM states WHERE name = 'Plateau';

-- Federal Capital Territory (6 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Abaji', id FROM states WHERE name = 'Federal Capital Territory';
INSERT INTO local_governments (name, state_id) SELECT 'Abuja Municipal', id FROM states WHERE name = 'Federal Capital Territory';
INSERT INTO local_governments (name, state_id) SELECT 'Bwari', id FROM states WHERE name = 'Federal Capital Territory';
INSERT INTO local_governments (name, state_id) SELECT 'Gwagwalada', id FROM states WHERE name = 'Federal Capital Territory';
INSERT INTO local_governments (name, state_id) SELECT 'Kuje', id FROM states WHERE name = 'Federal Capital Territory';
INSERT INTO local_governments (name, state_id) SELECT 'Kwali', id FROM states WHERE name = 'Federal Capital Territory';

-- Adamawa (21 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Demsa', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Fufore', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Ganye', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Girei', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Gombi', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Guyuk', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Hong', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Jada', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Lamurde', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Madagali', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Maiha', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Mayo-Belwa', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Michika', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Mubi North', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Mubi South', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Numan', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Shelleng', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Song', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Toungo', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Yola North', id FROM states WHERE name = 'Adamawa';
INSERT INTO local_governments (name, state_id) SELECT 'Yola South', id FROM states WHERE name = 'Adamawa';

-- Bauchi (20 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Alkaleri', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Bauchi', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Bogoro', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Damban', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Darazo', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Dass', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Gamawa', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Ganjuwa', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Giade', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Itas/Gadau', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Jama''are', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Katagum', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Kirfi', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Misau', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Ningi', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Shira', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Tafawa Balewa', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Toro', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Warji', id FROM states WHERE name = 'Bauchi';
INSERT INTO local_governments (name, state_id) SELECT 'Zaki', id FROM states WHERE name = 'Bauchi';

-- Borno (27 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Abadam', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Askira/Uba', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Bama', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Bayo', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Biu', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Chibok', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Damboa', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Dikwa', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Gubio', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Guzamala', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Gwoza', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Hawul', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Jere', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Kaga', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Kala/Balge', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Konduga', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Kukawa', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Kwaya Kusar', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Mafa', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Magumeri', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Maiduguri', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Marte', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Mobbar', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Monguno', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Ngala', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Nganzai', id FROM states WHERE name = 'Borno';
INSERT INTO local_governments (name, state_id) SELECT 'Shani', id FROM states WHERE name = 'Borno';

-- Gombe (11 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Akko', id FROM states WHERE name = 'Gombe';
INSERT INTO local_governments (name, state_id) SELECT 'Balanga', id FROM states WHERE name = 'Gombe';
INSERT INTO local_governments (name, state_id) SELECT 'Billiri', id FROM states WHERE name = 'Gombe';
INSERT INTO local_governments (name, state_id) SELECT 'Dukku', id FROM states WHERE name = 'Gombe';
INSERT INTO local_governments (name, state_id) SELECT 'Funakaye', id FROM states WHERE name = 'Gombe';
INSERT INTO local_governments (name, state_id) SELECT 'Gombe', id FROM states WHERE name = 'Gombe';
INSERT INTO local_governments (name, state_id) SELECT 'Kaltungo', id FROM states WHERE name = 'Gombe';
INSERT INTO local_governments (name, state_id) SELECT 'Kwami', id FROM states WHERE name = 'Gombe';
INSERT INTO local_governments (name, state_id) SELECT 'Nafada', id FROM states WHERE name = 'Gombe';
INSERT INTO local_governments (name, state_id) SELECT 'Shongom', id FROM states WHERE name = 'Gombe';
INSERT INTO local_governments (name, state_id) SELECT 'Yamaltu/Deba', id FROM states WHERE name = 'Gombe';

-- Taraba (16 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Ardo Kola', id FROM states WHERE name = 'Taraba';
INSERT INTO local_governments (name, state_id) SELECT 'Bali', id FROM states WHERE name = 'Taraba';
INSERT INTO local_governments (name, state_id) SELECT 'Donga', id FROM states WHERE name = 'Taraba';
INSERT INTO local_governments (name, state_id) SELECT 'Gashaka', id FROM states WHERE name = 'Taraba';
INSERT INTO local_governments (name, state_id) SELECT 'Gassol', id FROM states WHERE name = 'Taraba';
INSERT INTO local_governments (name, state_id) SELECT 'Ibi', id FROM states WHERE name = 'Taraba';
INSERT INTO local_governments (name, state_id) SELECT 'Jalingo', id FROM states WHERE name = 'Taraba';
INSERT INTO local_governments (name, state_id) SELECT 'Karim Lamido', id FROM states WHERE name = 'Taraba';
INSERT INTO local_governments (name, state_id) SELECT 'Kurmi', id FROM states WHERE name = 'Taraba';
INSERT INTO local_governments (name, state_id) SELECT 'Lau', id FROM states WHERE name = 'Taraba';
INSERT INTO local_governments (name, state_id) SELECT 'Sardauna', id FROM states WHERE name = 'Taraba';
INSERT INTO local_governments (name, state_id) SELECT 'Takum', id FROM states WHERE name = 'Taraba';
INSERT INTO local_governments (name, state_id) SELECT 'Ussa', id FROM states WHERE name = 'Taraba';
INSERT INTO local_governments (name, state_id) SELECT 'Wukari', id FROM states WHERE name = 'Taraba';
INSERT INTO local_governments (name, state_id) SELECT 'Yorro', id FROM states WHERE name = 'Taraba';
INSERT INTO local_governments (name, state_id) SELECT 'Zing', id FROM states WHERE name = 'Taraba';

-- Yobe (17 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Bade', id FROM states WHERE name = 'Yobe';
INSERT INTO local_governments (name, state_id) SELECT 'Bursari', id FROM states WHERE name = 'Yobe';
INSERT INTO local_governments (name, state_id) SELECT 'Damaturu', id FROM states WHERE name = 'Yobe';
INSERT INTO local_governments (name, state_id) SELECT 'Fika', id FROM states WHERE name = 'Yobe';
INSERT INTO local_governments (name, state_id) SELECT 'Fune', id FROM states WHERE name = 'Yobe';
INSERT INTO local_governments (name, state_id) SELECT 'Geidam', id FROM states WHERE name = 'Yobe';
INSERT INTO local_governments (name, state_id) SELECT 'Gujba', id FROM states WHERE name = 'Yobe';
INSERT INTO local_governments (name, state_id) SELECT 'Gulani', id FROM states WHERE name = 'Yobe';
INSERT INTO local_governments (name, state_id) SELECT 'Jakusko', id FROM states WHERE name = 'Yobe';
INSERT INTO local_governments (name, state_id) SELECT 'Karasuwa', id FROM states WHERE name = 'Yobe';
INSERT INTO local_governments (name, state_id) SELECT 'Machina', id FROM states WHERE name = 'Yobe';
INSERT INTO local_governments (name, state_id) SELECT 'Nangere', id FROM states WHERE name = 'Yobe';
INSERT INTO local_governments (name, state_id) SELECT 'Nguru', id FROM states WHERE name = 'Yobe';
INSERT INTO local_governments (name, state_id) SELECT 'Potiskum', id FROM states WHERE name = 'Yobe';
INSERT INTO local_governments (name, state_id) SELECT 'Tarmuwa', id FROM states WHERE name = 'Yobe';
INSERT INTO local_governments (name, state_id) SELECT 'Yunusari', id FROM states WHERE name = 'Yobe';
INSERT INTO local_governments (name, state_id) SELECT 'Yusufari', id FROM states WHERE name = 'Yobe';

-- Jigawa (27 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Auyo', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Babura', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Biriniwa', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Birnin Kudu', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Buji', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Dutse', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Gagarawa', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Garki', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Gumel', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Guri', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Gwaram', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Gwiwa', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Hadejia', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Jahun', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Kafin Hausa', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Kaugama', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Kazaure', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Kiri Kasama', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Kiyawa', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Maigatari', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Malam Madori', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Miga', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Ringim', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Roni', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Sule Tankarkar', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Taura', id FROM states WHERE name = 'Jigawa';
INSERT INTO local_governments (name, state_id) SELECT 'Yankwashi', id FROM states WHERE name = 'Jigawa';

-- Kaduna (23 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Birnin Gwari', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Chikun', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Giwa', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Igabi', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Ikara', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Jaba', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Jema''a', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Kachia', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Kaduna North', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Kaduna South', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Kagarko', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Kajuru', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Kaura', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Kauru', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Kubau', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Kudan', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Lere', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Makarfi', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Sabon Gari', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Sanga', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Soba', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Zangon Kataf', id FROM states WHERE name = 'Kaduna';
INSERT INTO local_governments (name, state_id) SELECT 'Zaria', id FROM states WHERE name = 'Kaduna';

-- Kano (44 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Ajingi', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Albasu', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Bagwai', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Bebeji', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Bichi', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Bunkure', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Dala', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Dambatta', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Dawakin Kudu', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Dawakin Tofa', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Doguwa', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Fagge', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Gabasawa', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Garko', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Garun Mallam', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Gaya', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Gezawa', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Gwale', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Gwarzo', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Kabo', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Kano Municipal', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Karaye', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Kibiya', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Kiru', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Kumbotso', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Kunchi', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Kura', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Madobi', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Makoda', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Minjibir', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Nasarawa', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Rano', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Rimin Gado', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Rogo', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Shanono', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Sumaila', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Takai', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Tarauni', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Tofa', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Tsanyawa', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Tudun Wada', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Ungogo', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Warawa', id FROM states WHERE name = 'Kano';
INSERT INTO local_governments (name, state_id) SELECT 'Wudil', id FROM states WHERE name = 'Kano';

-- Katsina (34 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Bakori', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Batagarawa', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Batsari', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Baure', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Bindawa', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Charanchi', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Dan Musa', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Dandume', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Danja', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Daura', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Dutsi', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Dutsin-Ma', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Faskari', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Funtua', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Ingawa', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Jibia', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Kafur', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Kaita', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Kankara', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Kankia', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Katsina', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Kurfi', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Kusada', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Mai''Adua', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Malumfashi', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Mani', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Mashi', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Matazu', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Musawa', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Rimi', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Sabuwa', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Safana', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Sandamu', id FROM states WHERE name = 'Katsina';
INSERT INTO local_governments (name, state_id) SELECT 'Zango', id FROM states WHERE name = 'Katsina';

-- Kebbi (21 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Aleiro', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Arewa Dandi', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Argungu', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Augie', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Bagudo', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Birnin Kebbi', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Bunza', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Dandi', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Fakai', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Gwandu', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Jega', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Kalgo', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Koko/Besse', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Maiyama', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Ngaski', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Sakaba', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Shanga', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Suru', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Wasagu/Danko', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Yauri', id FROM states WHERE name = 'Kebbi';
INSERT INTO local_governments (name, state_id) SELECT 'Zuru', id FROM states WHERE name = 'Kebbi';

-- Sokoto (23 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Binji', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Bodinga', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Dange Shuni', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Gada', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Goronyo', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Gudu', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Gwadabawa', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Illela', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Isa', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Kebbe', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Kware', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Rabah', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Sabon Birni', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Shagari', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Silame', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Sokoto North', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Sokoto South', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Tambuwal', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Tangaza', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Tureta', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Wamako', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Wurno', id FROM states WHERE name = 'Sokoto';
INSERT INTO local_governments (name, state_id) SELECT 'Yabo', id FROM states WHERE name = 'Sokoto';

-- Zamfara (14 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Anka', id FROM states WHERE name = 'Zamfara';
INSERT INTO local_governments (name, state_id) SELECT 'Bakura', id FROM states WHERE name = 'Zamfara';
INSERT INTO local_governments (name, state_id) SELECT 'Birnin Magaji/Kiyaw', id FROM states WHERE name = 'Zamfara';
INSERT INTO local_governments (name, state_id) SELECT 'Bukkuyum', id FROM states WHERE name = 'Zamfara';
INSERT INTO local_governments (name, state_id) SELECT 'Bungudu', id FROM states WHERE name = 'Zamfara';
INSERT INTO local_governments (name, state_id) SELECT 'Gummi', id FROM states WHERE name = 'Zamfara';
INSERT INTO local_governments (name, state_id) SELECT 'Gusau', id FROM states WHERE name = 'Zamfara';
INSERT INTO local_governments (name, state_id) SELECT 'Kaura Namoda', id FROM states WHERE name = 'Zamfara';
INSERT INTO local_governments (name, state_id) SELECT 'Maradun', id FROM states WHERE name = 'Zamfara';
INSERT INTO local_governments (name, state_id) SELECT 'Maru', id FROM states WHERE name = 'Zamfara';
INSERT INTO local_governments (name, state_id) SELECT 'Shinkafi', id FROM states WHERE name = 'Zamfara';
INSERT INTO local_governments (name, state_id) SELECT 'Talata Mafara', id FROM states WHERE name = 'Zamfara';
INSERT INTO local_governments (name, state_id) SELECT 'Tsafe', id FROM states WHERE name = 'Zamfara';
INSERT INTO local_governments (name, state_id) SELECT 'Zurmi', id FROM states WHERE name = 'Zamfara';

-- Abia (17 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Aba North', id FROM states WHERE name = 'Abia';
INSERT INTO local_governments (name, state_id) SELECT 'Aba South', id FROM states WHERE name = 'Abia';
INSERT INTO local_governments (name, state_id) SELECT 'Arochukwu', id FROM states WHERE name = 'Abia';
INSERT INTO local_governments (name, state_id) SELECT 'Bende', id FROM states WHERE name = 'Abia';
INSERT INTO local_governments (name, state_id) SELECT 'Ikwuano', id FROM states WHERE name = 'Abia';
INSERT INTO local_governments (name, state_id) SELECT 'Isiala Ngwa North', id FROM states WHERE name = 'Abia';
INSERT INTO local_governments (name, state_id) SELECT 'Isiala Ngwa South', id FROM states WHERE name = 'Abia';
INSERT INTO local_governments (name, state_id) SELECT 'Isuikwuato', id FROM states WHERE name = 'Abia';
INSERT INTO local_governments (name, state_id) SELECT 'Obi Ngwa', id FROM states WHERE name = 'Abia';
INSERT INTO local_governments (name, state_id) SELECT 'Ohafia', id FROM states WHERE name = 'Abia';
INSERT INTO local_governments (name, state_id) SELECT 'Osisioma Ngwa', id FROM states WHERE name = 'Abia';
INSERT INTO local_governments (name, state_id) SELECT 'Ugwunagbo', id FROM states WHERE name = 'Abia';
INSERT INTO local_governments (name, state_id) SELECT 'Ukwa East', id FROM states WHERE name = 'Abia';
INSERT INTO local_governments (name, state_id) SELECT 'Ukwa West', id FROM states WHERE name = 'Abia';
INSERT INTO local_governments (name, state_id) SELECT 'Umuahia North', id FROM states WHERE name = 'Abia';
INSERT INTO local_governments (name, state_id) SELECT 'Umuahia South', id FROM states WHERE name = 'Abia';
INSERT INTO local_governments (name, state_id) SELECT 'Umu Nneochi', id FROM states WHERE name = 'Abia';

-- Anambra (21 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Aguata', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Anambra East', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Anambra West', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Anaocha', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Awka North', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Awka South', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Ayamelum', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Dunukofia', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Ekwusigo', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Idemili North', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Idemili South', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Ihiala', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Njikoka', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Nnewi North', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Nnewi South', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Ogbaru', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Onitsha North', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Onitsha South', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Orumba North', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Orumba South', id FROM states WHERE name = 'Anambra';
INSERT INTO local_governments (name, state_id) SELECT 'Oyi', id FROM states WHERE name = 'Anambra';

-- Ebonyi (13 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Abakaliki', id FROM states WHERE name = 'Ebonyi';
INSERT INTO local_governments (name, state_id) SELECT 'Afikpo North', id FROM states WHERE name = 'Ebonyi';
INSERT INTO local_governments (name, state_id) SELECT 'Afikpo South', id FROM states WHERE name = 'Ebonyi';
INSERT INTO local_governments (name, state_id) SELECT 'Ebonyi', id FROM states WHERE name = 'Ebonyi';
INSERT INTO local_governments (name, state_id) SELECT 'Ezza North', id FROM states WHERE name = 'Ebonyi';
INSERT INTO local_governments (name, state_id) SELECT 'Ezza South', id FROM states WHERE name = 'Ebonyi';
INSERT INTO local_governments (name, state_id) SELECT 'Ikwo', id FROM states WHERE name = 'Ebonyi';
INSERT INTO local_governments (name, state_id) SELECT 'Ishielu', id FROM states WHERE name = 'Ebonyi';
INSERT INTO local_governments (name, state_id) SELECT 'Ivo', id FROM states WHERE name = 'Ebonyi';
INSERT INTO local_governments (name, state_id) SELECT 'Izzi', id FROM states WHERE name = 'Ebonyi';
INSERT INTO local_governments (name, state_id) SELECT 'Ohaozara', id FROM states WHERE name = 'Ebonyi';
INSERT INTO local_governments (name, state_id) SELECT 'Ohaukwu', id FROM states WHERE name = 'Ebonyi';
INSERT INTO local_governments (name, state_id) SELECT 'Onicha', id FROM states WHERE name = 'Ebonyi';

-- Enugu (17 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Aninri', id FROM states WHERE name = 'Enugu';
INSERT INTO local_governments (name, state_id) SELECT 'Awgu', id FROM states WHERE name = 'Enugu';
INSERT INTO local_governments (name, state_id) SELECT 'Enugu East', id FROM states WHERE name = 'Enugu';
INSERT INTO local_governments (name, state_id) SELECT 'Enugu North', id FROM states WHERE name = 'Enugu';
INSERT INTO local_governments (name, state_id) SELECT 'Enugu South', id FROM states WHERE name = 'Enugu';
INSERT INTO local_governments (name, state_id) SELECT 'Ezeagu', id FROM states WHERE name = 'Enugu';
INSERT INTO local_governments (name, state_id) SELECT 'Igbo Etiti', id FROM states WHERE name = 'Enugu';
INSERT INTO local_governments (name, state_id) SELECT 'Igbo Eze North', id FROM states WHERE name = 'Enugu';
INSERT INTO local_governments (name, state_id) SELECT 'Igbo Eze South', id FROM states WHERE name = 'Enugu';
INSERT INTO local_governments (name, state_id) SELECT 'Isi Uzo', id FROM states WHERE name = 'Enugu';
INSERT INTO local_governments (name, state_id) SELECT 'Nkanu East', id FROM states WHERE name = 'Enugu';
INSERT INTO local_governments (name, state_id) SELECT 'Nkanu West', id FROM states WHERE name = 'Enugu';
INSERT INTO local_governments (name, state_id) SELECT 'Nsukka', id FROM states WHERE name = 'Enugu';
INSERT INTO local_governments (name, state_id) SELECT 'Oji River', id FROM states WHERE name = 'Enugu';
INSERT INTO local_governments (name, state_id) SELECT 'Udenu', id FROM states WHERE name = 'Enugu';
INSERT INTO local_governments (name, state_id) SELECT 'Udi', id FROM states WHERE name = 'Enugu';
INSERT INTO local_governments (name, state_id) SELECT 'Uzo-Uwani', id FROM states WHERE name = 'Enugu';

-- Imo (27 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Aboh Mbaise', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Ahiazu Mbaise', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Ehime Mbano', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Ezinihitte', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Ideato North', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Ideato South', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Ihitte/Uboma', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Ikeduru', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Isiala Mbano', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Isu', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Mbaitoli', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Ngor Okpala', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Njaba', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Nkwerre', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Nwangele', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Obowo', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Oguta', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Ohaji/Egbema', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Okigwe', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Onuimo', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Orlu', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Orsu', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Oru East', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Oru West', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Owerri Municipal', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Owerri North', id FROM states WHERE name = 'Imo';
INSERT INTO local_governments (name, state_id) SELECT 'Owerri West', id FROM states WHERE name = 'Imo';

-- Akwa Ibom (31 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Abak', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Eastern Obolo', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Eket', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Esit Eket', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Essien Udim', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Etim Ekpo', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Etinan', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Ibeno', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Ibesikpo Asutan', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Ibiono Ibom', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Ika', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Ikono', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Ikot Abasi', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Ikot Ekpene', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Ini', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Itu', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Mbo', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Mkpat Enin', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Nsit Atai', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Nsit Ibom', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Nsit Ubium', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Obot Akara', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Okobo', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Onna', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Oron', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Oruk Anam', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Udung Uko', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Ukanafun', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Uruan', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Urue Offong/Oruko', id FROM states WHERE name = 'Akwa Ibom';
INSERT INTO local_governments (name, state_id) SELECT 'Uyo', id FROM states WHERE name = 'Akwa Ibom';

-- Bayelsa (8 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Brass', id FROM states WHERE name = 'Bayelsa';
INSERT INTO local_governments (name, state_id) SELECT 'Ekeremor', id FROM states WHERE name = 'Bayelsa';
INSERT INTO local_governments (name, state_id) SELECT 'Kolokuma/Opokuma', id FROM states WHERE name = 'Bayelsa';
INSERT INTO local_governments (name, state_id) SELECT 'Nembe', id FROM states WHERE name = 'Bayelsa';
INSERT INTO local_governments (name, state_id) SELECT 'Ogbia', id FROM states WHERE name = 'Bayelsa';
INSERT INTO local_governments (name, state_id) SELECT 'Sagbama', id FROM states WHERE name = 'Bayelsa';
INSERT INTO local_governments (name, state_id) SELECT 'Southern Ijaw', id FROM states WHERE name = 'Bayelsa';
INSERT INTO local_governments (name, state_id) SELECT 'Yenagoa', id FROM states WHERE name = 'Bayelsa';

-- Cross River (18 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Abi', id FROM states WHERE name = 'Cross River';
INSERT INTO local_governments (name, state_id) SELECT 'Akamkpa', id FROM states WHERE name = 'Cross River';
INSERT INTO local_governments (name, state_id) SELECT 'Akpabuyo', id FROM states WHERE name = 'Cross River';
INSERT INTO local_governments (name, state_id) SELECT 'Bakassi', id FROM states WHERE name = 'Cross River';
INSERT INTO local_governments (name, state_id) SELECT 'Bekwarra', id FROM states WHERE name = 'Cross River';
INSERT INTO local_governments (name, state_id) SELECT 'Biase', id FROM states WHERE name = 'Cross River';
INSERT INTO local_governments (name, state_id) SELECT 'Boki', id FROM states WHERE name = 'Cross River';
INSERT INTO local_governments (name, state_id) SELECT 'Calabar Municipal', id FROM states WHERE name = 'Cross River';
INSERT INTO local_governments (name, state_id) SELECT 'Calabar South', id FROM states WHERE name = 'Cross River';
INSERT INTO local_governments (name, state_id) SELECT 'Etung', id FROM states WHERE name = 'Cross River';
INSERT INTO local_governments (name, state_id) SELECT 'Ikom', id FROM states WHERE name = 'Cross River';
INSERT INTO local_governments (name, state_id) SELECT 'Obanliku', id FROM states WHERE name = 'Cross River';
INSERT INTO local_governments (name, state_id) SELECT 'Obubra', id FROM states WHERE name = 'Cross River';
INSERT INTO local_governments (name, state_id) SELECT 'Obudu', id FROM states WHERE name = 'Cross River';
INSERT INTO local_governments (name, state_id) SELECT 'Odukpani', id FROM states WHERE name = 'Cross River';
INSERT INTO local_governments (name, state_id) SELECT 'Ogoja', id FROM states WHERE name = 'Cross River';
INSERT INTO local_governments (name, state_id) SELECT 'Yakurr', id FROM states WHERE name = 'Cross River';
INSERT INTO local_governments (name, state_id) SELECT 'Yala', id FROM states WHERE name = 'Cross River';

-- Delta (25 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Aniocha North', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Aniocha South', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Bomadi', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Burutu', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Ethiope East', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Ethiope West', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Ika North East', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Ika South', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Isoko North', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Isoko South', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Ndokwa East', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Ndokwa West', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Okpe', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Oshimili North', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Oshimili South', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Patani', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Sapele', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Udu', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Ughelli North', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Ughelli South', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Ukwuani', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Uvwie', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Warri North', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Warri South', id FROM states WHERE name = 'Delta';
INSERT INTO local_governments (name, state_id) SELECT 'Warri South West', id FROM states WHERE name = 'Delta';

-- Edo (18 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Akoko-Edo', id FROM states WHERE name = 'Edo';
INSERT INTO local_governments (name, state_id) SELECT 'Egor', id FROM states WHERE name = 'Edo';
INSERT INTO local_governments (name, state_id) SELECT 'Esan Central', id FROM states WHERE name = 'Edo';
INSERT INTO local_governments (name, state_id) SELECT 'Esan North-East', id FROM states WHERE name = 'Edo';
INSERT INTO local_governments (name, state_id) SELECT 'Esan South-East', id FROM states WHERE name = 'Edo';
INSERT INTO local_governments (name, state_id) SELECT 'Esan West', id FROM states WHERE name = 'Edo';
INSERT INTO local_governments (name, state_id) SELECT 'Etsako Central', id FROM states WHERE name = 'Edo';
INSERT INTO local_governments (name, state_id) SELECT 'Etsako East', id FROM states WHERE name = 'Edo';
INSERT INTO local_governments (name, state_id) SELECT 'Etsako West', id FROM states WHERE name = 'Edo';
INSERT INTO local_governments (name, state_id) SELECT 'Igueben', id FROM states WHERE name = 'Edo';
INSERT INTO local_governments (name, state_id) SELECT 'Ikpoba-Okha', id FROM states WHERE name = 'Edo';
INSERT INTO local_governments (name, state_id) SELECT 'Orhionmwon', id FROM states WHERE name = 'Edo';
INSERT INTO local_governments (name, state_id) SELECT 'Oredo', id FROM states WHERE name = 'Edo';
INSERT INTO local_governments (name, state_id) SELECT 'Ovia North-East', id FROM states WHERE name = 'Edo';
INSERT INTO local_governments (name, state_id) SELECT 'Ovia South-West', id FROM states WHERE name = 'Edo';
INSERT INTO local_governments (name, state_id) SELECT 'Owan East', id FROM states WHERE name = 'Edo';
INSERT INTO local_governments (name, state_id) SELECT 'Owan West', id FROM states WHERE name = 'Edo';
INSERT INTO local_governments (name, state_id) SELECT 'Uhunmwonde', id FROM states WHERE name = 'Edo';

-- Rivers (23 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Abua/Odual', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Ahoada East', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Ahoada West', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Akuku-Toru', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Andoni', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Asari-Toru', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Bonny', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Degema', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Emuoha', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Eleme', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Etche', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Gokana', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Ikwerre', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Khana', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Obio/Akpor', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Ogba/Egbema/Ndoni', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Ogu/Bolo', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Okrika', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Omuma', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Opobo/Nkoro', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Oyigbo', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Port Harcourt', id FROM states WHERE name = 'Rivers';
INSERT INTO local_governments (name, state_id) SELECT 'Tai', id FROM states WHERE name = 'Rivers';

-- Ekiti (16 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Ado Ekiti', id FROM states WHERE name = 'Ekiti';
INSERT INTO local_governments (name, state_id) SELECT 'Efon', id FROM states WHERE name = 'Ekiti';
INSERT INTO local_governments (name, state_id) SELECT 'Ekiti East', id FROM states WHERE name = 'Ekiti';
INSERT INTO local_governments (name, state_id) SELECT 'Ekiti South-West', id FROM states WHERE name = 'Ekiti';
INSERT INTO local_governments (name, state_id) SELECT 'Ekiti West', id FROM states WHERE name = 'Ekiti';
INSERT INTO local_governments (name, state_id) SELECT 'Emure', id FROM states WHERE name = 'Ekiti';
INSERT INTO local_governments (name, state_id) SELECT 'Gbonyin', id FROM states WHERE name = 'Ekiti';
INSERT INTO local_governments (name, state_id) SELECT 'Ido Osi', id FROM states WHERE name = 'Ekiti';
INSERT INTO local_governments (name, state_id) SELECT 'Ijero', id FROM states WHERE name = 'Ekiti';
INSERT INTO local_governments (name, state_id) SELECT 'Ikere', id FROM states WHERE name = 'Ekiti';
INSERT INTO local_governments (name, state_id) SELECT 'Ikole', id FROM states WHERE name = 'Ekiti';
INSERT INTO local_governments (name, state_id) SELECT 'Ilejemeje', id FROM states WHERE name = 'Ekiti';
INSERT INTO local_governments (name, state_id) SELECT 'Irepodun/Ifelodun', id FROM states WHERE name = 'Ekiti';
INSERT INTO local_governments (name, state_id) SELECT 'Ise/Orun', id FROM states WHERE name = 'Ekiti';
INSERT INTO local_governments (name, state_id) SELECT 'Moba', id FROM states WHERE name = 'Ekiti';
INSERT INTO local_governments (name, state_id) SELECT 'Oye', id FROM states WHERE name = 'Ekiti';

-- Lagos (20 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Agege', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Ajeromi-Ifelodun', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Alimosho', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Amuwo-Odofin', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Apapa', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Badagry', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Epe', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Eti Osa', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Ibeju-Lekki', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Ifako-Ijaiye', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Ikeja', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Ikorodu', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Kosofe', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Lagos Island', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Lagos Mainland', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Mushin', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Ojo', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Oshodi-Isolo', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Shomolu', id FROM states WHERE name = 'Lagos';
INSERT INTO local_governments (name, state_id) SELECT 'Surulere', id FROM states WHERE name = 'Lagos';

-- Ogun (20 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Abeokuta North', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Abeokuta South', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Ado-Odo/Ota', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Yewa North', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Yewa South', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Ewekoro', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Ifo', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Ijebu East', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Ijebu North', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Ijebu North East', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Ijebu Ode', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Ikenne', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Imeko Afon', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Ipokia', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Obafemi Owode', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Odeda', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Odogbolu', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Ogun Waterside', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Remo North', id FROM states WHERE name = 'Ogun';
INSERT INTO local_governments (name, state_id) SELECT 'Sagamu', id FROM states WHERE name = 'Ogun';

-- Ondo (18 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Akoko North-East', id FROM states WHERE name = 'Ondo';
INSERT INTO local_governments (name, state_id) SELECT 'Akoko North-West', id FROM states WHERE name = 'Ondo';
INSERT INTO local_governments (name, state_id) SELECT 'Akoko South-West', id FROM states WHERE name = 'Ondo';
INSERT INTO local_governments (name, state_id) SELECT 'Akoko South-East', id FROM states WHERE name = 'Ondo';
INSERT INTO local_governments (name, state_id) SELECT 'Akure North', id FROM states WHERE name = 'Ondo';
INSERT INTO local_governments (name, state_id) SELECT 'Akure South', id FROM states WHERE name = 'Ondo';
INSERT INTO local_governments (name, state_id) SELECT 'Ese Odo', id FROM states WHERE name = 'Ondo';
INSERT INTO local_governments (name, state_id) SELECT 'Idanre', id FROM states WHERE name = 'Ondo';
INSERT INTO local_governments (name, state_id) SELECT 'Ifedore', id FROM states WHERE name = 'Ondo';
INSERT INTO local_governments (name, state_id) SELECT 'Ilaje', id FROM states WHERE name = 'Ondo';
INSERT INTO local_governments (name, state_id) SELECT 'Ile Oluji/Okeigbo', id FROM states WHERE name = 'Ondo';
INSERT INTO local_governments (name, state_id) SELECT 'Irele', id FROM states WHERE name = 'Ondo';
INSERT INTO local_governments (name, state_id) SELECT 'Odigbo', id FROM states WHERE name = 'Ondo';
INSERT INTO local_governments (name, state_id) SELECT 'Okitipupa', id FROM states WHERE name = 'Ondo';
INSERT INTO local_governments (name, state_id) SELECT 'Ondo East', id FROM states WHERE name = 'Ondo';
INSERT INTO local_governments (name, state_id) SELECT 'Ondo West', id FROM states WHERE name = 'Ondo';
INSERT INTO local_governments (name, state_id) SELECT 'Ose', id FROM states WHERE name = 'Ondo';
INSERT INTO local_governments (name, state_id) SELECT 'Owo', id FROM states WHERE name = 'Ondo';

-- Osun (30 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Aiyedaade', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Aiyedire', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Atakunmosa East', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Atakunmosa West', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Boluwaduro', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Boripe', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Ede North', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Ede South', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Egbedore', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Ejigbo', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Ife Central', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Ife East', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Ife North', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Ife South', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Ifedayo', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Ifelodun', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Ila', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Ilesa East', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Ilesa West', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Irepodun', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Irewole', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Isokan', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Iwo', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Obokun', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Odo Otin', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Ola Oluwa', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Olorunda', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Oriade', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Orolu', id FROM states WHERE name = 'Osun';
INSERT INTO local_governments (name, state_id) SELECT 'Osogbo', id FROM states WHERE name = 'Osun';

-- Oyo (33 LGAs)
INSERT INTO local_governments (name, state_id) SELECT 'Afijio', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Akinyele', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Atiba', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Atisbo', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Egbeda', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Ibadan North', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Ibadan North-East', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Ibadan North-West', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Ibadan South-East', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Ibadan South-West', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Ibarapa Central', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Ibarapa East', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Ibarapa North', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Ido', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Irepo', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Iseyin', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Itesiwaju', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Iwajowa', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Kajola', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Lagelu', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Ogbomosho North', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Ogbomosho South', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Ogo Oluwa', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Olorunsogo', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Oluyole', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Ona Ara', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Orelope', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Ori Ire', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Oyo East', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Oyo West', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Saki East', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Saki West', id FROM states WHERE name = 'Oyo';
INSERT INTO local_governments (name, state_id) SELECT 'Surulere', id FROM states WHERE name = 'Oyo';
