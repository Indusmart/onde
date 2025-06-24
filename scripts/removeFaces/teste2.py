#!/usr/bin/env python3
import sys
import argparse

from OCC.Core.STEPControl import STEPControl_Reader, STEPControl_Writer
from OCC.Core.IFSelect import IFSelect_RetDone
from OCC.Core.TopExp import TopExp_Explorer
from OCC.Core.TopAbs import TopAbs_FACE
from OCC.Core.TopoDS import TopoDS_Compound, TopoDS_Builder
from OCC.Core.STEPControl import STEPControl_AsIs

def read_step(filename):
    reader = STEPControl_Reader()
    status = reader.ReadFile(filename)
    if status != IFSelect_RetDone:
        sys.exit(f"Error: não foi possível ler o arquivo '{filename}'")
    reader.TransferRoots()
    return reader.OneShape()


def write_step(shape, filename):
    writer = STEPControl_Writer()
    # Transferir o shape sem modificar a representação
    writer.Transfer(shape, STEPControl_AsIs)
    status = writer.Write(filename)
    if status != IFSelect_RetDone:
        sys.exit(f"Error: não foi possível escrever o arquivo '{filename}'")


def filter_faces(shape, skip_indices):
    builder = TopoDS_Builder()
    compound = TopoDS_Compound()
    builder.MakeCompound(compound)
    explorer = TopExp_Explorer(shape, TopAbs_FACE)
    idx = 1
    while explorer.More():
        face = explorer.Current()
        if idx not in skip_indices:
            builder.Add(compound, face)
        idx += 1
        explorer.Next()
    return compound


def parse_args():
    parser = argparse.ArgumentParser(
        description='Remove faces especificadas de um arquivo STEP via linha de comando.'
    )
    parser.add_argument('input', help='Arquivo STEP de entrada')
    parser.add_argument('output', help='Arquivo STEP de saída')
    parser.add_argument(
        '--faces', '-f',
        nargs='+',
        type=int,
        required=True,
        help='Índices (1-based) das faces a serem removidas'
    )
    return parser.parse_args()


def main():
    args = parse_args()
    shape = read_step(args.input)
    clean_shape = filter_faces(shape, set(args.faces))
    write_step(clean_shape, args.output)
    print(f"Arquivo '{args.output}' gerado sem as faces: {sorted(args.faces)}")

if __name__ == '__main__':
    main()
