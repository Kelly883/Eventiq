declare module 'crypto-js/sha256' {
  const SHA256: (message: string) => { toString: (encoder: { stringify: (data: unknown) => string }) => string };
  export default SHA256;
}

declare module 'crypto-js/enc-hex' {
  const Hex: { stringify: (data: unknown) => string };
  export default Hex;
}
