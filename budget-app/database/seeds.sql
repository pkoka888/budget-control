-- Budget Control - Seed Data
-- Financial Tips and Default Categories

-- Default Categories
INSERT INTO categories (user_id, name, type, color, icon, description) VALUES
(-1, 'Jídlo a nápoje', 'expense', '#FF6B6B', 'utensils', 'Nákupy potravin, restaurace, kavárny'),
(-1, 'Doprava', 'expense', '#4ECDC4', 'car', 'Benzín, veřejná doprava, parkování'),
(-1, 'Ubytování', 'expense', '#45B7D1', 'home', 'Nájem, energie, údržba'),
(-1, 'Zábava', 'expense', '#F7DC6F', 'film', 'Kino, hry, zájezdy'),
(-1, 'Zdraví', 'expense', '#BB8FCE', 'heart', 'Lékařské služby, léky, posilovny'),
(-1, 'Vzdělání', 'expense', '#85C1E2', 'book', 'Kurzy, knihy, školení'),
(-1, 'Odevy a obuv', 'expense', '#F8B88B', 'shopping', 'Oblečení, boty, módní doplňky'),
(-1, 'Osobní péče', 'expense', '#A8E6CF', 'sparkles', 'Holení, vlasy, kosmetika'),
(-1, 'Domácnost', 'expense', '#DCEDC1', 'home', 'Nářadí, čistidla, dekorace'),
(-1, 'Životní pojistka', 'expense', '#FFD3B6', 'shield', 'Pojistné poplatky'),
(-1, 'Půjčky a úroky', 'expense', '#FFAAA5', 'trending-down', 'Splátky půjček a úroky'),
(-1, 'Dary a charita', 'expense', '#FF8B94', 'gift', 'Dary, příspěvky, charita'),
(-1, 'Daně', 'expense', '#6C5CE7', 'briefcase', 'Daňové platby'),
(-1, 'Ostatní výdaje', 'expense', '#A29BFE', 'help-circle', 'Ostatní nezařazené výdaje'),
(-1, 'Plat', 'income', '#00B894', 'briefcase', 'Měsíční plat'),
(-1, 'Freelance', 'income', '#FF7675', 'user-check', 'Freelance příjmy'),
(-1, 'Investiční výnosy', 'income', '#00CEC9', 'trending-up', 'Výnosy z investic, úroky'),
(-1, 'Vratka a vrácení', 'income', '#6C5CE7', 'rotate-ccw', 'Vrácené částky'),
(-1, 'Ostatní příjmy', 'income', '#A29BFE', 'plus-circle', 'Ostatní příjmy');

-- Financial Tips and Guides
INSERT INTO tips (title, content, category, tags, priority, is_published) VALUES (
    'Jak začít s budgetováním',
    '<h2>Klíčové kroky k efektivnímu budgetování</h2>
    <p><strong>Budgetování</strong> je základem zdravých osobních financí. Zde je jednoduchý průvodce, jak začít:</p>
    <h3>1. Sledujte své příjmy a výdaje</h3>
    <p>Prvním krokem je pochopení, kolik peněz máte a kam jdou. Shromažďujte účtenky, sledujte bankovní výpisy a zaznamenávejte všechny transakce alespoň jeden měsíc.</p>
    <h3>2. Kategorizujte své výdaje</h3>
    <p>Rozdělte výdaje do kategorií jako jídlo, doprava, zábava atd. Tímto způsobem uvidíte, kde můžete snížit náklady.</p>
    <h3>3. Nastavte rozpočet</h3>
    <p>Na základě svých historických dat vytvořte realistický rozpočet pro každou kategorii. Pamatujte na pravidlo 50/30/20: 50% na potřeby, 30% na přání, 20% na úspory.</p>
    <h3>4. Monitorujte a přizpůsobujte</h3>
    <p>Pravidelně kontrolujte, zda se držíte svého rozpočtu. Buďte flexibilní a přizpůsobujte se podle potřeby.</p>
    <h3>5. Oslavujte úspěchy</h3>
    <p>Když dosáhnete svých cílů, oslavujte! Malé úspěchy vás motivují pokračovat.</p>',
    'Základy',
    'budgetování,začátek,finanční-plánování',
    1,
    1
), (
    '50/30/20 Rozpočtovací pravidlo',
    '<h2>Jednoduché pravidlo pro vyvážený rozpočet</h2>
    <p>Pravidlo 50/30/20 je jeden z nejjednoduších a nejúčinnějších způsobů, jak si zorganizovat svůj rozpočet.</p>
    <h3>Jak to funguje?</h3>
    <ul>
    <li><strong>50% na potřeby:</strong> Nejdůležitější výdaje - bydlení, jídlo, doprava, pojištění</li>
    <li><strong>30% na přání:</strong> Diskrační výdaje - zábava, hobby, restaurace</li>
    <li><strong>20% na úspory a splácení dluhů:</strong> Budoucnost - spořicí účet, investice, splátky</li>
    </ul>
    <h3>Příklad</h3>
    <p>Máte měsíční příjem 50 000 Kč:</p>
    <ul>
    <li>Potřeby: 25 000 Kč (půjčovné, jídlo, doprava)</li>
    <li>Přání: 15 000 Kč (zábava, restaurace, hobby)</li>
    <li>Úspory: 10 000 Kč (spořicí účet, investice)</li>
    </ul>
    <h3>Flexibilní přístup</h3>
    <p>Toto pravidlo není striktní. Pokud máte vyšší náklady na bydlení, můžete si upravit procenta podle svých potřeb. Důležité je, aby se součet rovnal 100% a aby se věnovala alespoň nějaká částka úsporám.</p>',
    'Základy',
    'rozpočet,50-30-20,pravidlo',
    2,
    1
), (
    'Jak snížit výdaje na jídlo',
    '<h2>Praktické tipy na ušetření za jídlem</h2>
    <p>Výdaje na jídlo jsou často největší položkou v domácím rozpočtu. Zde jsou způsoby, jak je snížit bez obětování kvality:</p>
    <h3>Plánování a nákupní seznam</h3>
    <ul>
    <li>Naplánujte si jídelníček na týden dopředu</li>
    <li>Vytvořte si nákupní seznam a držte se jej</li>
    <li>Nakupujte s menšími dětmi, aby si vezly méně zbytečných věcí</li>
    </ul>
    <h3>Nakupujte chytře</h3>
    <ul>
    <li>Kupujte značky obchodů - jsou často stejně kvalitní, ale levnější</li>
    <li>Kontrolujte ceny za jednotku (Kč/kg)</li>
    <li>Nakupujte sezónní ovoce a zeleninu</li>
    <li>Využívejte slevy a promoce</li>
    </ul>
    <h3>Snižte chození do restaurací</h3>
    <ul>
    <li>Vaříte doma, je 3-5x levnější než restaurace</li>
    <li>Vezměte si oběd z domu do práce</li>
    <li>Omezitevypití na svátky a speciální příležitosti</li>
    </ul>
    <h3>Skladování a zbytky</h3>
    <ul>
    <li>Zmrazujte zbytky z obědů a večeří</li>
    <li>Připravujte větší množství jednoduchých jídel</li>
    <li>Nevyhazujte zůstávající potraviny</li>
    </ul>',
    'Úspory',
    'jídlo,snížení-výdajů,nákupy',
    1,
    1
), (
    'Investování pro začátečníky',
    '<h2>Prvníkroky do světa investic</h2>
    <p>Investování nemusí být složité. Zde je jednoduchý průvodce, jak začít:</p>
    <h3>Proč investovat?</h3>
    <p>Peníze v spořicím účtu ztrácejí na hodnotě vlivem inflace. Investice vám mohou pomoci zvýšit vaše bohatství v dlouhodobém horizontu.</p>
    <h3>Jaké jsou vaše možnosti?</h3>
    <ul>
    <li><strong>Spořicí účty:</strong> Nejbezpečnější, ale nízké výnosy</li>
    <li><strong>Dluhopisy:</strong> Nižší riziko, střední výnosy</li>
    <li><strong>Akcie:</strong> Vyšší riziko, vyšší potenciální výnosy</li>
    <li><strong>Fondy:</strong> Diverzifikované, spravované profesionály</li>
    </ul>
    <h3>Pravidlo 70/30</h3>
    <p>Pokud jste začátečník, investujte 70% do bezpečných aktiv (dluhopisy, akciové fondy) a 30% do agresivnějších (jednotlivé akcie).</p>
    <h3>Dlouhodobý přístup</h3>
    <p>Nejlepší investoři kupují a drží. Nepanikařte, když ceny klesnou. Historie ukazuje, že trhy se vždy zotavují.</p>
    <h3>Začněte malý</h3>
    <p>Nejste si jisti? Začněte s malými částkami. Můžete si koupit dílky akcií nebo fondů i za pár set Kč.</p>',
    'Investování',
    'akcie,fondy,riziko,dlouhodobý-horizont',
    1,
    1
), (
    'Как vytvořit fond na nouzi',
    '<h2>Finanční bezpečnostní síť</h2>
    <p>Fond na nouzi je jeden z nejdůležitějších aspektů finanční bezpečnosti. Zde je, jak ho vytvořit:</p>
    <h3>Kolik byste měli mít?</h3>
    <p>Finančníci doporučují mít ve fondu na nouzi 3-6 měsíců výdajů. Pokud máte měsíční výdaje 30 000 Kč, měli byste mít 90 000 - 180 000 Kč.</p>
    <h3>Jak na to?</h3>
    <ul>
    <li>Otevřete si oddělený spořicí účet</li>
    <li>Začněte malým, třeba 500 Kč měsíčně</li>
    <li>Zvyšujte částku, jak je to možné</li>
    <li>Nespájujte si jej s běžným účtem - měl by být snadno dostupný, ale "mimo dosah"</li>
    </ul>
    <h3>Kdy jej použít?</h3>
    <p>Fond na nouzi by měl být určen pouze na nepředvídané výdaje nebo dočasný příjmový výpadek. Nepoužívejte jej na plánované dovolené nebo nákupy.</p>
    <h3>Obnovení fondu</h3>
    <p>Pokud musíte fond využít, snažte se jej co nejdříve doplnit zpět na původní úroveň.</p>',
    'Bezpečnost',
    'fond-na-nouzi,úspora,nouzové-situace',
    1,
    1
), (
    'Splácení dluhů: Strategie a tipy',
    '<h2>Chytré způsoby, jak se zbavit dluhů</h2>
    <p>Dluhy mohou být stresující, ale s pravým plánem je můžete eliminovat. Zde jsou efektivní strategie:</p>
    <h3>Metoda Snowball</h3>
    <ul>
    <li>Vymezte všechny své dluhy od nejmenšího po největší</li>
    <li>Splacejte nejmenší dluh tím, že na něj vložíte co nejvíce peněz</li>
    <li>Jakmile jej splatíte, přesuňte tuto částku na další nejmenší dluh</li>
    <li>Pokračujte, dokud nejsou všechny dluhy vyrovnány</li>
    </ul>
    <h3>Metoda Laviny</h3>
    <ul>
    <li>Zaměřte se na dluhy s nejvyšší úrokovou sazbou</li>
    <li>Splacejte je agresivně, zatímco ostatní splácíte minimálně</li>
    <li>Ušetříte na úrocích</li>
    </ul>
    <h3>Refinancování</h3>
    <p>Pokud máte vysoké úrokové sazby, zvažte refinancování u jiné banky. I 1% úspora se může v čase výrazně sčítat.</p>
    <h3>Komunikace s věřiteli</h3>
    <p>Pokud máte potíže se splácením, mluvte se svým věřitelem. Mnozí se mohou přiblížit k úpravě podmínek.</p>',
    'Dluh',
    'dluhy,splácení,strategie',
    2,
    1
), (
    'Finanční cíle a jak je dosáhnout',
    '<h2>Plánování finančního budoucnosti</h2>
    <p>Samotné investování není dost - potřebujete i cíle. Zde je, jak si je nastavit a dosáhnout:</p>
    <h3>Typy finančních cílů</h3>
    <ul>
    <li><strong>Krátkodobé (0-1 rok):</strong> Dovolená, nový mobil, oprava auta</li>
    <li><strong>Střednědobé (1-5 let):</strong> Splácení dluhu, koupi auta, stavba domu</li>
    <li><strong>Dlouhodobé (5+ let):</strong> Důchod, koupi nemovitosti, vzdělání dětí</li>
    </ul>
    <h3>SMART cíle</h3>
    <p>Vaše cíle by měly být:</p>
    <ul>
    <li><strong>S</strong> - Specifické: "Ušetřit 100 000 Kč na dovolenou" místo "ušetřit na dovolenou"</li>
    <li><strong>M</strong> - Měřitelné: Jasná částka nebo metrika</li>
    <li><strong>A</strong> - Dosažitelné: Realistické vzhledem k vašim příjmům</li>
    <li><strong>R</strong> - Relevantní: Důležité pro vás</li>
    <li><strong>T</strong> - Časové vymezené: Konkrétní termín</li>
    </ul>
    <h3>Plán dosažení</h3>
    <ul>
    <li>Rozdělte velký cíl na menší kroky</li>
    <li>Určete měsíční úspory potřebné k dosažení cíle</li>
    <li>Trackujte svůj pokrok</li>
    <li>Slavte milníky</li>
    </ul>',
    'Plánování',
    'cíle,plánování,finanční-budoucnost',
    1,
    1
), (
    'Jak ovládat své impulzní nákupy',
    '<h2>Praktické strategie proti zbytečným výdajům</h2>
    <p>Impulzní nákupy mohou zničit i ten nejlepší rozpočet. Zde je, jak jim odolat:</p>
    <h3>Pochopte své spouštěče</h3>
    <ul>
    <li>Co vás nutí koupit věci, které nepotřebujete? Stres? Nudeness? FOMO?</li>
    <li>Jakmile identifikujete spouštěče, můžete se jim vyhnout nebo najít zdravější copingové mechanismy</li>
    </ul>
    <h3>Pravidlo 30 dní</h3>
    <p>Než si koupíte něco, co není nezbytné, počkejte 30 dní. Často zjistíte, že chcete vůbec víc. Toto zvláště funguje pro online nákupy - odeberte si newsletter obchodů!</p>
    <h3>Odstraňte pokušení</h3>
    <ul>
    <li>Odhlaste se z propagačních e-mailů</li>
    <li>Odinstalujte nákupní aplikace z telefonu</li>
    <li>Nechodte do obchodů bez seznamu</li>
    </ul>
    <h3>Bezhotovostní platby</h3>
    <p>Pokud je to možné, používejte hotovost místo karty. Psikologicky je těžší strávit peníze, které fyzicky vidíte a cítíte.</p>
    <h3>Najděte alternativy</h3>
    <p>Když budete chtít koupit něco zbytečného, pusťte si svůj oblíbený seriál, jděte na procházku nebo volejte kamaráda místo nákupního spree.</p>',
    'Úspory',
    'impulzní-nákupy,kontrola-výdajů,psychologie-peněz',
    1,
    1
), (
    'Sladky a zdánlivě levný ťa úroky',
    '<h2>Jak vysoké úroky vám mohou škodit</h2>
    <p>Vysoké úrokové sazby jsou jedním z největších úkrytů finančního Vám. Zde je, co byste měli vědět:</p>
    <h3>Jak úroky fungují?</h3>
    <p>Úroky jsou poplatek za půjčené peníze. Pokud si půjčíte 10 000 Kč s 10% úrokovou sazbou, budete dlužit 11 000 Kč.</p>
    <h3>Složené úroky</h3>
    <p>Složené úroky jsou "úroky na úroky". To znamená, že váš dluh roste exponenciálně. To je obzvláště problematické u kreditních karet.</p>
    <h3>Průměrné úrokové sazby</h3>
    <ul>
    <li>Hypotéky: 2-4%</li>
    <li>Osobní půjčky: 5-10%</li>
    <li>Kreditní karty: 15-25%</li>
    </ul>
    <h3>Jak se vyhnout vysokým úrokům?</h3>
    <ul>
    <li>Udržujte si dobrý kreditní rating</li>
    <li>Porovnávejte nabídky od různých poskytovatelů</li>
    <li>Splácejte více, když je to možné</li>
    <li>Izolujte se od kreditních karet s vysokou sazbou</li>
    </ul>',
    'Dluh',
    'úroky,dluh,kreditní-karty',
    2,
    1
);

-- Default user categories would be created on registration
-- This seed is for global tips that apply to all users
