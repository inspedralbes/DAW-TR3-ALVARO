/**
 * TICKETMONSTER - TEST DE CONCURRÈNCIA EXTREMA (RACE CONDITION TEST)
 * 
 * Aquest script de Node.js emula l'escenari més problemàtic d'una plataforma
 * de venta d'entrades: Múltiples usuaris (bots en aquest cas) intentant premer 
 * "Comprar" a la mateixa butaca al mateix milisegon.
 * 
 * L'objectiu matemàtic d'aquest test és assegurar-nos que gràcies als sub-processos
 * "DB::transaction" i "lockForUpdate()" de Laravel amb innoDB, solament 1
 * de les 100 peticions simultànees tornin un HTTP 200 (Èxit) i la resta fallin
 * justament.
 * 
 * Requisits: Executar amb Node.js v18+ (Utilitza native fetch)
 */

const API_URL = 'http://localhost:8000/api/seats/reserve'; // Simulem demanar la butaca ID 1
const NUM_REQUESTS = 100; // Quantitat de "clics" simultanis competint per 1 cadira

async function attack() {
    console.log(`🚀 Iniciant Atac de Concurrència a l'API Laravel (${API_URL})`);
    console.log(`👥 Generant ${NUM_REQUESTS} peticions paral·leles exactes al mateix lloc...\n`);

    const requests = [];

    // Fabriquem les 100 peticions al mateix temps (NO fem await aquí)
    for (let i = 0; i < NUM_REQUESTS; i++) {
        const uniqueClientId = `user_bot_${i}`;
        
        requests.push(
            fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    seat_id: 1, // <--- Enviem la butaca 1
                    session_id: `concurrency_session_${uniqueClientId}`
                })
            }).then(async (response) => {
                const data = await response.json();
                return {
                    botId: i,
                    status: response.status,
                    success: response.ok,
                    message: data.message || 'Sense missatge'
                };
            }).catch(e => ({
                botId: i,
                status: 500,
                success: false,
                message: e.message
            }))
        );
    }

    // Await all resolts a la vegada
    const start = Date.now();
    const results = await Promise.all(requests);
    const timeTaken = Date.now() - start;

    let successCount = 0;
    let failCount = 0;

    results.forEach(res => {
        if (res.success) {
            successCount++;
            console.log(`✅ [Bot ${res.botId}] MÀGIA! Ha aconseguit la butaca! (HTTP ${res.status})`);
        } else {
            failCount++;
            // Don't print all 99 errors to not pollute console, just count them
        }
    });

    console.log(`\n=================================================`);
    console.log(`📊 RESULTATS DEL TEST D'ESTRÈS DE BASE DE DADES`);
    console.log(`=================================================`);
    console.log(`Temps de resolució: ${timeTaken}ms`);
    console.log(`Total d'Intents   : ${NUM_REQUESTS}`);
    console.log(`Victòries (Seient assignat): ${successCount}`);
    console.log(`Rebutjats (Col·lisió evitada completament): ${failCount}`);

    if (successCount === 1 && failCount === NUM_REQUESTS - 1) {
        console.log(`\n🏆 TEST PASSAT AMB ÈXIT L'algoritme de "Pessimistic Locking" funciona prèviament. Zero "Double Bookings".`);
    } else if (successCount > 1) {
        console.log(`\n❌ ERROR CRÍTIC (RACE CONDITION): S'ha venut el mateix seient a ${successCount} persones!`);
    } else {
        console.log(`\n⚠️ ADVERTÈNCIA: O bé la butaca ja estava venuda abans o el servidor ha caigut. Victòries: ${successCount}`);
    }
}

attack();
