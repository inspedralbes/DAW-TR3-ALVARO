describe('Flux Normal de Compra d\'Entrades', () => {
  it('Ha de permetre a un usuari fer login i comprar bitllets pel primer esdeveniment', () => {
    // 1. Visit homepage
    cy.visit('/')
    cy.contains('Pròxims Esdeveniments').should('be.visible')
    
    // 2. Login
    cy.visit('/login')
    cy.get('input[type="email"]').type('admin@ticketmonster.com')
    cy.get('input[type="password"]').type('admin1234')
    cy.get('button[type="submit"]').click()
    
    // Accept successful login redirect
    cy.url().should('eq', 'http://localhost:3000/')
    cy.contains('TICKETMONSTER').should('be.visible')

    // 3. Navigate to Event details
    // Assume there's a link or button directly to an event (From the 'Comprar Entrades' button)
    cy.contains('Comprar Entrades', { matchCase: false }).click()

    // 4. Select a seat on the map
    cy.get('.seat-map-container').should('exist')
    // Find an 'available' seat, it usually lacks 'sold' or 'reserved' classes
    // We target a specific or first available seat
    cy.get('button[aria-label^="Seleccionar Fila"]').not('.opacity-50').not('.bg-primary').not('.bg-red-500').first().click({force: true})
    
    // Verify it appeared in the summary
    cy.contains('€').should('be.visible')
    
    // 5. Procedir al pagament
    cy.contains('Procedir al Pagament', { matchCase: false }).click()
    cy.url().should('include', '/checkout')
    
    // 6. Confirm purchase
    cy.contains('Pagar i Confirmar', { matchCase: false }).click()
    
    // 7. Success state
    cy.contains('Pagament Confirmat!').should('be.visible')
    cy.contains('Veure les teves entrades').click()
    
    // 8. Verify ticket is on 'My Tickets' page
    cy.url().should('include', '/my-tickets')
    cy.contains('admin@ticketmonster.com').should('be.visible')
  })
})
