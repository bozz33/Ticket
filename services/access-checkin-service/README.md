# Access Check-in Service

Service NestJS satellite pour validation terrain des passes d'acces.

## Responsabilites

- recevoir les projections de passes depuis Laravel;
- valider un QR code ou une reference de pass de facon atomique;
- empecher le double scan;
- tracer agent, porte, appareil et mode offline;
- exposer des resumes temps reel par evenement.

## Regle d'ownership

Laravel reste source de verite commerciale. Ce service possede une projection operationnelle des passes et le journal de scan terrain.
